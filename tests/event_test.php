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

namespace local_custompage;

use advanced_testcase;
use local_custompage\custom_context\context_custompage;
use local_custompage\event\custompage_viewed;
use stdClass;

/**
 * Event tests for local_custompage.
 *
 * @package    local_custompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_custompage\event\custompage_viewed
 * @group      local_custompage
 */
final class event_test extends advanced_testcase {
    /**
     * Viewed event should snapshot the real custom pages table.
     */
    public function test_custompage_viewed_uses_local_custompages_table(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $pagedata = new stdClass();
        $pagedata->name = 'Viewed page';
        $pagedata->title = '';
        $page = \local_custompage\local\helpers\page::create_page($pagedata);
        $context = context_custompage::instance((int) $page->get('id'));

        $event = custompage_viewed::create_from_object($page->to_record(), $context);

        $this->assertSame('local_custompages', $event->objecttable);
        $snapshot = $event->get_record_snapshot('local_custompages', (int) $page->get('id'));
        $this->assertSame((int) $page->get('id'), (int) $snapshot->id);
        $this->assertSame('Viewed page', $snapshot->name);
    }
}
