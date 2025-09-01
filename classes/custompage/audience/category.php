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
 * The backend class for Category-based audience type
 *
 * @package     local_custompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category extends base {
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

        $mform->addElement('autocomplete', 'categories', get_string('categories', 'core'), $categoryoptions, ['multiple' => true]);
        $mform->addRule('categories', null, 'required', null, 'client');
        $mform->addHelpButton('categories', 'categories', 'local_custompage');
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

        $categories = $this->get_configdata()['categories'] ?? [];

        // Return empty result if no categories are selected.
        if (empty($categories)) {
            return ['', '1=0', []];
        }

        $prefix = database::generate_param_name() . '_';
        [$insql, $inparams] = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, $prefix);

        // Ensure parameter names and aliases are unique.
        $userenrolments = database::generate_alias();
        $enrolments = database::generate_alias();
        $courses = database::generate_alias();

        $join = "
            JOIN {user_enrolments} {$userenrolments} ON {$userenrolments}.userid = {$usertablealias}.id
            JOIN {enrol} {$enrolments} ON {$enrolments}.id = {$userenrolments}.enrolid
            JOIN {course} {$courses} ON {$courses}.id = {$enrolments}.courseid";
        $where = "{$courses}.category {$insql} AND {$userenrolments}.status = " . ENROL_USER_ACTIVE;

        return [$join, $where, $inparams];
    }

    /**
     * Return user friendly name of this audience type
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('categories', 'core');
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
        $categorynames = [];

        if (!empty($categoryids)) {
            $categories = $DB->get_records_list('course_categories', 'id', $categoryids, 'name ASC');
            foreach ($categories as $category) {
                $categorynames[] = $category->name;
            }
        }

        return $this->format_description_for_multiselect($categorynames);
    }

    /**
     * If the current user is able to add this audience.
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function user_can_add(): bool {
        return has_capability('moodle/category:view', context_system::instance());
    }

    /**
     * If the current user is able to edit this audience.
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function user_can_edit(): bool {
        return has_capability('moodle/category:view', context_system::instance());
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
        
        // Only available if there are visible categories.
        return $DB->record_exists('course_categories', ['visible' => 1]);
    }
}
