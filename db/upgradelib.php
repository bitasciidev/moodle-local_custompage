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

/**
 * Shared helper functions used by multiple db/upgrade.php steps.
 *
 * Extracted so the original 2024110403/2024110405 upgrade steps and the
 * defensive 2026062301 re-check step backfill data identically instead of
 * maintaining two copies of the same logic.
 *
 * @package    local_custompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backfill depth and path for all existing custom pages.
 *
 * Root pages are set to depth 1 with path "/{id}". Direct children are set
 * to depth 2 with path "/{parentid}/{id}". Safe to call repeatedly; it
 * always recomputes both fields from the current parent/id data.
 */
function local_custompage_upgrade_backfill_depth_and_path(): void {
    global $DB;

    $roots = $DB->get_records_select(
        'local_custompages',
        'parent IS NULL OR parent = 0',
        [],
        'name ASC',
        'id'
    );
    foreach ($roots as $root) {
        $DB->set_field('local_custompages', 'depth', 1, ['id' => $root->id]);
        $DB->set_field('local_custompages', 'path', '/' . $root->id, ['id' => $root->id]);
    }

    $children = $DB->get_records_select(
        'local_custompages',
        'parent IS NOT NULL AND parent != 0',
        [],
        'name ASC',
        'id, parent'
    );
    foreach ($children as $child) {
        $DB->set_field('local_custompages', 'depth', 2, ['id' => $child->id]);
        $DB->set_field('local_custompages', 'path', '/' . $child->parent . '/' . $child->id, ['id' => $child->id]);
    }
}

/**
 * Mark root pages that have at least one child as containers.
 *
 * Safe to call repeatedly; it always recomputes from the current parent
 * relationships.
 */
function local_custompage_upgrade_backfill_iscontainer(): void {
    global $DB;

    $roots = $DB->get_records_select('local_custompages', 'parent IS NULL OR parent = 0', [], '', 'id');
    foreach ($roots as $root) {
        if ($DB->record_exists('local_custompages', ['parent' => $root->id])) {
            $DB->set_field('local_custompages', 'iscontainer', 1, ['id' => $root->id]);
        }
    }
}
