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

declare(strict_types=1);

namespace local_custompage\reportbuilder\local\systemreports;

use coding_exception;
use core_reportbuilder\local\helpers\format;
use local_custompage\local\models\page;
use local_custompage\reportbuilder\local\entities\custompages;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\report\action;
use core_reportbuilder\local\report\column;
use html_writer;
use lang_string;
use moodle_url;
use pix_icon;
use core_reportbuilder\system_report;
use stdClass;
use local_custompage\local\helpers\audience;
use local_custompage\permission;

/**
 * Pages list
 *
 * @package     local_custompage
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pages_list extends system_report {
    /**
     * Initialise the report
     */
    protected function initialise(): void {

        $custompageentity = new custompages();
        $entitymainalias = $custompageentity->get_table_alias('local_custompages');

        $this->set_main_table('local_custompages', $entitymainalias);
        $this->add_entity($custompageentity);

        // Select fields required for actions, permission checks, and row class callbacks.
        $this->add_base_fields("{$entitymainalias}.id");

        // Limit the returned list to those pages the current user can access.
        [$where, $params] = audience::user_pages_list_access_sql($entitymainalias);
        $this->add_base_condition_sql($where, $params);

        // Join user entity for "User modified" column.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');

        $this->add_entity($entityuser
            ->add_join("LEFT JOIN {user} {$entityuseralias} ON {$entityuseralias}.id = {$entitymainalias}.usermodified"));

        $this->add_columns($custompageentity);
        $this->add_filters($custompageentity);
        $this->add_actions();

        $this->set_downloadable(false);
    }

    /**
     * Ensure we can view the report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return permission::can_view_pages_list();
    }

    /**
     * Add columns to report
     *
     * @throws coding_exception
     */
    protected function add_columns(custompages $custompageentity): void {

        $tablealias = $this->get_main_table_alias();

        $this->add_column_from_entity('custompages:name')
            ->set_is_sortable(false)
            ->add_callback(static function (?string $value, stdClass $page): string {
                $pageobj = new page(0, $page);
                $editable = permission::can_edit_page($pageobj);
                $url = $editable
                    ? new moodle_url('/local/custompage/edit.php', ['id' => $page->id])
                    : new moodle_url('/local/custompage/view.php', ['id' => $page->id]);
                $displayname = $pageobj->get_formatted_name_with_indent();
                return html_writer::link($url, $displayname);
            });

        $this->add_column((new column(
            'title',
            new lang_string('title', 'local_custompage'),
            $custompageentity->get_entity_name()
        ))
        ->set_type(column::TYPE_TEXT)
        ->add_fields(implode(', ', [
          "{$tablealias}.id",
          "{$tablealias}.title",
          "{$tablealias}.contextid",
        ]))
        ->set_is_sortable(false, ["{$tablealias}.title"])
        ->add_callback(static function (string $value, stdClass $page): string {
            $pageobj = new page(0, $page);
            return $pageobj->get_formatted_title();
        }));

        // Time modified column.
        $this->add_column((new column(
            'timemodified',
            new lang_string('timemodified', 'core_reportbuilder'),
            $custompageentity->get_entity_name()
        ))
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timemodified")
            ->set_is_sortable(false)
            ->add_callback([format::class, 'userdate']));

        // The user who modified the report.
        $this->add_column_from_entity('user:fullname')
            ->set_title(new lang_string('usermodified', 'reportbuilder'))
            ->set_is_sortable(false);

        $this->set_initial_sort_column('custompages:name', SORT_ASC);
    }

    /**
     * Add filters to report
     */
    protected function add_filters(custompages $custompageentity): void {

        $filters = [
        "{$custompageentity->get_entity_name()}:name",
        "{$custompageentity->get_entity_name()}:title",
        ];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Add actions to report
     */
    protected function add_actions(): void {
        // Edit content action.
        $this->add_action((new action(
            new moodle_url('/local/custompage/edit.php', ['id' => ':id']),
            new pix_icon('t/right', ''),
            [],
            false,
            new lang_string('editpagecontent', 'local_custompage')
        ))
            ->add_callback(function (stdClass $row): bool {
                return permission::can_edit_page(new page(0, $row));
            }));

        // Edit details action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/edit', ''),
            ['data-action' => 'page-edit', 'data-page-id' => ':id'],
            false,
            new lang_string('editpagedetails', 'local_custompage')
        ))
            ->add_callback(function (stdClass $row): bool {
                return permission::can_edit_page(new page(0, $row));
            }));

        // Preview action.
        $this->add_action((new action(
            new moodle_url('/local/custompage/view.php', ['id' => ':id']),
            new pix_icon('i/search', ''),
            [],
            false,
            new lang_string('viewpage', 'local_custompage')
        ))
            ->add_callback(function (stdClass $row): bool {
                // We check this only to give the action to editors, because normal users can just click on the report name.
                return permission::can_view_page(new page(0, $row));
            }));

        // Move up action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/up', ''),
            ['data-action' => 'page-move-up', 'data-page-id' => ':id'],
            false,
            new lang_string('moveup', 'local_custompage')
        ))
            ->add_callback(function (stdClass $row): bool {
                return permission::can_edit_page(new page(0, $row));
            }));

        // Move down action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/down', ''),
            ['data-action' => 'page-move-down', 'data-page-id' => ':id'],
            false,
            new lang_string('movedown', 'local_custompage')
        ))
            ->add_callback(function (stdClass $row): bool {
                return permission::can_edit_page(new page(0, $row));
            }));

        // Delete action.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/delete', ''),
            ['data-action' => 'page-delete', 'data-page-id' => ':id', 'data-page-name' => ':name'],
            false,
            new lang_string('deletepage', 'local_custompage')
        ))
            ->add_callback(function (stdClass $row): bool {

                // Ensure data name attribute is properly formatted.
                $page = new page(0, $row);
                $row->name = $page->get_formatted_name();

                // We don't check whether report is valid to ensure editor can always delete them.
                return permission::can_edit_page($page);
            }));
    }
}
