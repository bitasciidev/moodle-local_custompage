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
 * Main management page for custom pages listing.
 *
 * @package    local_custompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

use local_custompage\permission;
use core_reportbuilder\system_report_factory;
use local_custompage\reportbuilder\local\systemreports\pages_list;

require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

admin_externalpage_setup(
    'managecustompages',
    null,
    [],
    new moodle_url('/local/custompage/index.php'),
    ['pagelayout' => 'admin', 'nosearch' => true]
);

$PAGE->set_secondary_navigation(false);
$PAGE->set_heading('');
$PAGE->requires->js_call_amd('local_custompage/pages_list', 'init');

echo $OUTPUT->header();

// Header section with title and create button.
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
echo $OUTPUT->heading(get_string('custompages', 'local_custompage'), 2, 'mb-0');

if (permission::can_create_page()) {
    $renderer = $PAGE->get_renderer('local_custompage');
    echo $renderer->render_new_page_button();
}
echo html_writer::end_div();

// Pages list report.
try {
    $report = system_report_factory::create(pages_list::class, \core\context\system::instance());
    echo html_writer::div($report->output(), 'pages-list-container mt-4');
} catch (Exception $e) {
    debugging('Error loading pages list: ' . $e->getMessage(), DEBUG_DEVELOPER);
    echo $OUTPUT->notification(get_string('error'), 'error');
}

echo $OUTPUT->footer();
