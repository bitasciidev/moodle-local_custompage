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
 * The backend class for Cohort-based audience type
 *
 * @package     local_custompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort extends base {
    /**
     * Adds audience's elements to the given mform
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @throws coding_exception
     */
    public function get_config_form(MoodleQuickForm $mform): void {
        global $DB;

        // Get available cohorts.
        $cohorts = $DB->get_records('cohort', ['visible' => 1], 'name ASC', 'id, name');
        $cohortoptions = [];
        foreach ($cohorts as $cohort) {
            $cohortoptions[$cohort->id] = $cohort->name;
        }

        $mform->addElement('autocomplete', 'cohorts', get_string('cohorts', 'core_cohort'), $cohortoptions, ['multiple' => true]);
        $mform->addRule('cohorts', null, 'required', null, 'client');
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

        $cohorts = $this->get_configdata()['cohorts'];
        $prefix = database::generate_param_name() . '_';
        [$insql, $inparams] = $DB->get_in_or_equal($cohorts, SQL_PARAMS_NAMED, $prefix);

        // Ensure parameter names and aliases are unique.
        $cohortmembers = database::generate_alias();

        $join = "JOIN {cohort_members} {$cohortmembers} ON {$cohortmembers}.userid = {$usertablealias}.id";
        $where = "{$cohortmembers}.cohortid {$insql}";

        return [$join, $where, $inparams];
    }

    /**
     * Return user friendly name of this audience type
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('cohorts', 'core_cohort');
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

        $cohortids = $this->get_configdata()['cohorts'];
        $cohortnames = [];

        if (!empty($cohortids)) {
            $cohorts = $DB->get_records_list('cohort', 'id', $cohortids, 'name ASC');
            foreach ($cohorts as $cohort) {
                $cohortnames[] = $cohort->name;
            }
        }

        return $this->format_description_for_multiselect($cohortnames);
    }

    /**
     * If the current user is able to add this audience.
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function user_can_add(): bool {
        return has_capability('moodle/cohort:view', context_system::instance());
    }

    /**
     * If the current user is able to edit this audience.
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function user_can_edit(): bool {
        return has_capability('moodle/cohort:view', context_system::instance());
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
        
        // Only available if there are visible cohorts.
        return $DB->record_exists('cohort', ['visible' => 1]);
    }
}
