<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_custompage\custom_context;

use coding_exception;
use context;
use moodle_url;
use stdClass;


/**
 *  context_custompage.php description here.
 *
 * @package     local_custompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_custompage extends context {
    /**
     * Please use context_custompage::instance($pageid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     * @throws coding_exception
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_CUSTOMPAGE) {
            throw new coding_exception('Invalid $record->contextlevel in context_custompage constructor.');
        }
    }

    /**
     * Returns human readable context level name.
     * @return string the human-readable context level name.
     */
    public static function get_level_name() {
        return get_string('custompage', 'local_custompage');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param true $withprefix
     * @param false $short
     * @param true $escape
     * @return string the human-readable context name.
     */
    public function get_context_name($withprefix = true, $short = false, $escape = true) {
        global $DB;

        $name = '';
        if ($custompage = $DB->get_record('local_custompages', ['id' => $this->_instanceid])) {
            if ($withprefix) {
                $name = get_string('custompage', 'local_custompage').': ';
            }
            $name .= format_string($custompage->name, true, ['context' => $this]);
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/local/custompage/index.php', ['pageid' => $this->_instanceid]);
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @param string $sort
     * @return array
     */
    public function get_capabilities(string $sort = self::DEFAULT_CAPABILITY_SORT) {
        global $DB;

        return $DB->get_records_list('capabilities', 'contextlevel', [
        CONTEXT_CUSTOMPAGE,
        CONTEXT_BLOCK,
        ], $sort);
    }

    /**
     * Returns custompage context instance.
     *
     * @param int $pageid id from {custompages} table
     * @param int $strictness
     * @return context|bool context instance
     */
    public static function instance($pageid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_CUSTOMPAGE, $pageid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', ['contextlevel' => CONTEXT_CUSTOMPAGE, 'instanceid' => $pageid])) {
            if ($custompage = $DB->get_record('local_custompages', ['id' => $pageid], 'id,parent', $strictness)) {
                if ($custompage->parent) {
                    $parentcontext = self::instance($custompage->parent);
                    $record = context::insert_context_record(CONTEXT_CUSTOMPAGE, $custompage->id, $parentcontext->path);
                } else {
                    $record = context::insert_context_record(CONTEXT_CUSTOMPAGE, $custompage->id, '/'.SYSCONTEXTID, 0);
                }
            }
        }

        if ($record) {
            $context = new context_custompage($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Returns immediate child contexts of pages and all sub-pages,
     * children of sub-pages are not returned.
     *
     * @return array
     */
    public function get_child_contexts() {
        global $DB;

        if (empty($this->_path) || empty($this->_depth)) {
            debugging('Can not find child contexts of context '.$this->_id.' try rebuilding of context paths');
            return [];
        }

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = [$this->_path.'/%', $this->depth + 1, CONTEXT_CUSTOMPAGE];
        $records = $DB->get_records_sql($sql, $params);

        $result = [];
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

    /**
     * Create missing context instances at tenant context level
     */
    protected static function create_level_instances() {
        global $DB;

        $sql = "SELECT ".CONTEXT_CUSTOMPAGE.", sp.id
                  FROM {local_custompages} sp
                 WHERE NOT EXISTS (SELECT 'x'
                                     FROM {context} cx
                                    WHERE sp.id = cx.instanceid AND cx.contextlevel=".CONTEXT_CUSTOMPAGE.")";
        $contextdata = $DB->get_recordset_sql($sql);
        foreach ($contextdata as $context) {
            context::insert_context_record(CONTEXT_CUSTOMPAGE, $context->id, null);
        }
        $contextdata->close();
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        $sql = "
                  SELECT c.*
                    FROM {context} c
         LEFT OUTER JOIN {local_custompages} sp ON c.instanceid = sp.id
                   WHERE sp.id IS NULL AND c.contextlevel = ".CONTEXT_CUSTOMPAGE."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths and depths at custompage context level.
     *
     * @param bool $force
     */
    protected static function build_paths($force) {
        global $DB;

        $syscontextid = SYSCONTEXTID;

        if ($force ||
                $DB->record_exists_select('context', "contextlevel = ".CONTEXT_CUSTOMPAGE." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = $emptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
                $emptyclause    = "AND ({context}.path IS NULL OR {context}.depth = 0)";
            }

            $base = '/'.SYSCONTEXTID;

            // Normal top level pages.
            $sql = "UPDATE {context}
                       SET depth=2,
                           path=".$DB->sql_concat("'$base/'", 'id')."
                     WHERE contextlevel=".CONTEXT_CUSTOMPAGE."
                           AND EXISTS (SELECT 'x'
                                         FROM {local_custompages} sp
                                        WHERE sp.id = {context}.instanceid AND sp.depth=1)
                           $emptyclause";
            $DB->execute($sql);

            // Deeper pages - one query per depthlevel.
            $maxdepth = $DB->get_field_sql("SELECT MAX(depth) FROM {custompages}");
            for ($n = 2; $n <= $maxdepth; $n++) {
                $sql = "INSERT INTO {context_temp} (id, path, depth, locked)
                        SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1, ctx.locked
                          FROM {context} ctx
                          JOIN {local_custompages} sp
                            ON (sp.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_CUSTOMPAGE." AND sp.depth = $n)
                          JOIN {context} pctx ON (pctx.instanceid = sp.parent AND pctx.contextlevel = ".CONTEXT_CUSTOMPAGE.")
                         WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                               $ctxemptyclause";
                $trans = $DB->start_delegated_transaction();
                $DB->delete_records('context_temp');
                $DB->execute($sql);
                context::merge_context_temp_table();
                $DB->delete_records('context_temp');
                $trans->allow_commit();

            }
        }
    }
}

