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
 *  lib.php description here.
 *
 * @package local_custompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CONTEXT_CUSTOMPAGE', 75);

use core\output\inplace_editable;
use local_custompage\form\audience;
use local_custompage\local\models\page as page_persistent;
use local_custompage\manager;
use local_custompage\output\audience_heading_editable;
use local_custompage\output\page_name_editable;
use local_custompage\output\page_title_editable;
use local_custompage\permission;
use local_custompage\local\helpers\audience as audience_helper;

/**
 * Plugin inplace editable implementation
 *
 * @param string $itemtype
 * @param int $itemid
 * @param string $newvalue
 * @return inplace_editable|null
 */
function local_custompage_inplace_editable(string $itemtype, int $itemid, string $newvalue): ?inplace_editable {
    require_sesskey();
    switch ($itemtype) {
        case 'pagename':
            return page_name_editable::update($itemid, $newvalue);
        case 'pagetitle':
            return page_title_editable::update($itemid, $newvalue);
        case 'audienceheading':
            return audience_heading_editable::update($itemid, $newvalue);
    }
    return null;
}


/**
 * Return the audience form fragment
 *
 * @param array $params
 * @return string
 */
function local_custompage_output_fragment_audience_form(array $params): string {
    global $PAGE;

    $audienceform = new audience(null, null, 'post', '', [], true, [
    'pageid' => $params['pageid'],
    'classname' => $params['classname'],
    ]);
    $audienceform->set_data_for_dynamic_submission();

    $context = [
    'instanceid' => 0,
    'heading' => $params['title'],
    'headingeditable' => $params['title'],
    'form' => $audienceform->render(),
    'canedit' => true,
    'candelete' => true,
    'showormessage' => $params['showormessage'],
    ];

    $renderer = $PAGE->get_renderer('local_custompage');
    return $renderer->render_from_template('local_custompage/local/audience/form', $context);
}


/**
 * local_custompage_extend_navigation
 *
 * @param global_navigation $nav
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_custompage_extend_navigation(global_navigation $nav) {
    global $PAGE, $CFG, $USER;

    // First we need to find the pages based on user id.
    // If guest-login is enabled then we will also check for guest user otherwise only for logged-in user.

    $CFG->dbunmodifiedcustommenuitems = $CFG->custommenuitems;

    if (isloggedin()) {
        $userid = (int)$USER->id;
    } else if ($CFG->guestloginbutton) {
        $guest = guest_user();
        $userid = (int)$guest->id;
    }
    if (!$userid) {
        return;
    }
    $pages = audience_helper::user_pages_list($userid);

    foreach ($pages as $pageid) {
        $custompage = page_persistent::get_record(['id' => (int)$pageid]);
        $pagename = $custompage->get_formatted_name();
        $pagetitle = $custompage->get_formatted_title();
        if (!$pagetitle) {
            $pagetitle = $pagename;
        }

        $CFG->custommenuitems .= "\n" . "$pagetitle|/local/custompage/view.php?id=$pageid\n";

        if ($PAGE->context->contextlevel == CONTEXT_CUSTOMPAGE) {
            if ($pageid == $PAGE->context->instanceid) {
                // Add page node to homepage node.
                $frontpagenode = $nav->find('home', null);

                if (!$frontpagenode) {
                    $frontpagenode = $nav->add(
                        get_string('home'),
                        new moodle_url('/index.php'),
                        navigation_node::TYPE_ROOTNODE,
                        null
                    );
                    $frontpagenode->force_open();
                }
                $custompagenode = $frontpagenode->add(
                    $custompage->get_formatted_name(),
                    new moodle_url('/local/custompage/view.php', ['id' => $custompage->get('id')])
                );
                $custompagenode->make_active();
            }
        } else {
            // Add page node to homepage node.
            $frontpagenode = $PAGE->navigation->find('home', null);

            if (!$frontpagenode) {
                $frontpagenode = $PAGE->navigation->add(
                    get_string('home'),
                    new moodle_url('/index.php'),
                    navigation_node::TYPE_ROOTNODE,
                    null
                );
                $frontpagenode->force_open();
            }

            $frontpagenode->add(
                $pagename,
                new moodle_url('/local/custompage/view.php', ['id' => $pageid])
            );
        }
    }
}

/**
 * after_config hook
 * @return void
 */
function local_custompage_after_config() {
    global $CFG;
    $customcontextclasses = [
        CONTEXT_CUSTOMPAGE => 'local_custompage\\custom_context\\context_custompage',
    ];

    if (isset($CFG->custom_context_classes)) {
        $CFG->custom_context_classes = $CFG->custom_context_classes + $customcontextclasses;
    } else {
        $CFG->custom_context_classes = $customcontextclasses;
    }
}
