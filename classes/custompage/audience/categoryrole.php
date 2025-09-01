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

namespace local_custompage\custompage\audience;

use coding_exception;
use context_system;
use core_reportbuilder\local\helpers\database;
use dml_exception;
use local_custompage\local\audiences\base;
use MoodleQuickForm;

/**
 * The backend class for Category Role-based audience type
 *
 * @package     local_custompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class categoryrole extends base {
    /**
     * Adds audience's elements to the given mform
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @throws coding_exception
     */
    public function get_config_form(MoodleQuickForm $mform): void {
        global $DB;

        // Get available course categories.
        $categories = $DB->get_records('course_categories', ['visible' => 1], 'sortorder ASC, name ASC', 'id, name');
        $categoryoptions = [];
        foreach ($categories as $category) {
            $categoryoptions[$category->id] = $category->name;
        }

        // Get available roles.
        $roles = get_assignable_roles(context_system::instance(), ROLENAME_ALIAS);

        $mform->addElement('autocomplete', 'categories', get_string('categories', 'core'), $categoryoptions, ['multiple' => true]);
        $mform->addRule('categories', null, 'required', null, 'client');

        $mform->addElement('autocomplete', 'roles', get_string('selectrole', 'role'), $roles, ['multiple' => true]);
        $mform->addRule('roles', null, 'required', null, 'client');
    }

    /**
     * Helps to build SQL to retrieve users that matches the current page audience
     *
     * @param string $usertablealias
     * @return array array of three elements [$join, $where, $params]
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_sql(string $usertablealias): array {
        global $DB;

        $categories = $this->get_configdata()['categories'];
        $roles = $this->get_configdata()['roles'];

        $categoryprefix = database::generate_param_name() . '_';
        [$categoryinsql, $categoryparams] = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, $categoryprefix);

        $roleprefix = database::generate_param_name() . '_';
        [$roleinsql, $roleparams] = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, $roleprefix);

        // Ensure parameter names and aliases are unique.
        $roleassignments = database::generate_alias();
        $context = database::generate_alias();

        $join = "
            JOIN {role_assignments} {$roleassignments} ON {$roleassignments}.userid = {$usertablealias}.id
            JOIN {context} {$context} ON {$context}.id = {$roleassignments}.contextid";

        $where = "{$context}.contextlevel = " . CONTEXT_COURSECAT . " 
                  AND {$context}.instanceid {$categoryinsql} 
                  AND {$roleassignments}.roleid {$roleinsql}";

        return [$join, $where, $categoryparams + $roleparams];
    }

    /**
     * Return user friendly name of this audience type
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('categoryrole', 'local_custompage');
    }

    /**
     * Return the description for the audience.
     *
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_description(): string {
        global $DB;

        $categoryids = $this->get_configdata()['categories'];
        $roleids = $this->get_configdata()['roles'];

        $descriptions = [];

        if (!empty($categoryids)) {
            $categories = $DB->get_records_list('course_categories', 'id', $categoryids, 'name ASC');
            $categorynames = [];
            foreach ($categories as $category) {
                $categorynames[] = $category->name;
            }
            $descriptions[] = get_string('categories', 'core') . ': ' . implode(', ', $categorynames);
        }

        if (!empty($roleids)) {
            $roles = $DB->get_records_list('role', 'id', $roleids, 'name ASC');
            $rolenames = [];
            foreach ($roles as $role) {
                $rolenames[] = $role->name;
            }
            $descriptions[] = get_string('roles', 'core') . ': ' . implode(', ', $rolenames);
        }

        return implode('; ', $descriptions);
    }

    /**
     * If the current user is able to add this audience.
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function user_can_add(): bool {
        return has_capability('moodle/role:assign', context_system::instance());
    }

    /**
     * If the current user is able to edit this audience.
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function user_can_edit(): bool {
        return has_capability('moodle/role:assign', context_system::instance());
    }

    /**
     * If the current user is able to use this audience type
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function is_available(): bool {
        global $DB;
        
        // Only available if there are visible categories and assignable roles.
        return $DB->record_exists('course_categories', ['visible' => 1]) && 
               !empty(get_assignable_roles(context_system::instance()));
    }
}
