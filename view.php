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
 * View a custom page.
 *
 * @package    local_custompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

use local_custompage\custom_context\context_custompage;
use local_custompage\manager;
use local_custompage\permission;
use local_custompage\event\custompage_viewed;

require_once(__DIR__ . '/../../config.php');

$pageid = required_param('id', PARAM_INT);

// Validate page ID.
if ($pageid <= 0) {
    throw new moodle_exception('invalidpageid', 'local_custompage');
}

try {
    $context = context_custompage::instance($pageid);
    $page = manager::get_page_from_id($pageid);
} catch (dml_missing_record_exception $e) {
    throw new moodle_exception('pagenotfound', 'local_custompage');
} catch (Exception $e) {
    debugging('Error loading page: ' . $e->getMessage(), DEBUG_DEVELOPER);
    throw new moodle_exception('errorloadingpage', 'local_custompage');
}

require_login(null, true);
permission::require_can_view_page($page);
$PAGE->set_context($context);

// Log page view event.
try {
    $pageobject = $page->to_record();
    $event = custompage_viewed::create_from_object($pageobject, $context);
    $event->trigger();
} catch (Exception $e) {
    debugging('Error logging page view: ' . $e->getMessage(), DEBUG_DEVELOPER);
}

$pageurl = new moodle_url('/local/custompage/view.php', ['id' => $pageid]);
$pagetitle = $page->get_formatted_title() ?: $page->get_formatted_name();
$iscontainer = $page->is_container();

// Container pages cannot be edited.
if ($iscontainer && isset($USER->editing)) {
    $USER->editing = 0;
}

$PAGE->set_subpage((string)$pageid);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('local-custompage-view');

// Load theme block regions first to ensure standard regions (like the right drawer) remain the default.
$PAGE->blocks->get_regions();

// Only non-container pages should show blocks.
if (!$iscontainer) {
    $PAGE->blocks->add_region('content');
    $PAGE->blocks->add_region('side-pre');
}

$PAGE->set_title($pagetitle);
$PAGE->set_heading($page->get_formatted_name());
$PAGE->set_url($pageurl);

// Setup breadcrumb navigation.
try {
    manager::setup_page_breadcrumb($page->get_breadcrumb());
} catch (Exception $e) {
    debugging('Error setting up breadcrumb: ' . $e->getMessage(), DEBUG_DEVELOPER);
}

$renderer = $PAGE->get_renderer('local_custompage');
$showeditorheader = $PAGE->user_is_editing() &&
                   permission::can_edit_page($page) &&
                   !$iscontainer;

echo $OUTPUT->header();

// Only content pages can have blocks added to them.
if (!$iscontainer) {
    echo $OUTPUT->addblockbutton('content');
}

// Show editor header for non-container pages.
if ($showeditorheader) {
    try {
        echo $renderer->render_fullpage_editor_header($page);
    } catch (Exception $e) {
        debugging('Error rendering editor header: ' . $e->getMessage(), DEBUG_DEVELOPER);
        echo $OUTPUT->notification(get_string('erroreditorheader', 'local_custompage'), 'error');
    }
}

// Render page content blocks.
if (!$iscontainer) {
    echo $OUTPUT->custom_block_region('content');
}

echo $OUTPUT->footer();
