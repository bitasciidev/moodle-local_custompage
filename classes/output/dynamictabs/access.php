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

namespace local_custompage\output\dynamictabs;

use context_system;
use core\output\dynamic_tabs\base;
use local_custompage\local\models\page;
use local_custompage\reportbuilder\local\systemreports\page_access_list;
use local_custompage\permission;
use core_reportbuilder\system_report_factory;
use local_custompage\custom_context\context_custompage;
use renderer_base;

/**
 * Access dynamic tab
 *
 * @package     local_custompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access extends base {
    /**
     * Export this for use in a mustache template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $report = system_report_factory::create(
            page_access_list::class,
            context_system::instance(),
            'local_custompage',
            '',
            (int)$this->data['pageid'],
            ['id' => $this->data['pageid']]
        );
        $data['report'] = $report->output();
        return $data;
    }

    /**
     * The label to be displayed on the tab
     *
     * @return string
     */
    public function get_tab_label(): string {
        return get_string('access', 'core_reportbuilder');
    }

    /**
     * Check permission of the current user to access this tab
     *
     * @return bool
     */
    public function is_available(): bool {
        $pagepersistent = new page((int)$this->data['pageid']);
        return permission::can_edit_page($pagepersistent);
    }

    /**
     * Template to use to display tab contents
     *
     * @return string
     */
    public function get_template(): string {
        return 'local_custompage/local/dynamictabs/access';
    }
}
