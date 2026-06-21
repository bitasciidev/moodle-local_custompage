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
 * External service definitions for local_custompage.
 *
 * @package    local_custompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
  'local_custompage_audiences_delete' => [
    'classname'    => 'local_custompage\external\audiences\delete',
    'description'  => 'Delete audience of custompage',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/custompage:edit',
  ],
  'local_custompage_page_delete' => [
    'classname'    => 'local_custompage\external\page\delete',
    'description'  => 'Delete a custom page',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/custompage:edit',
  ],
  'local_custompage_page_update_sort_order' => [
    'classname'    => 'local_custompage\external\page\update_sort_order',
    'description'  => 'Update the sort order of custom pages',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/custompage:edit',
  ],
  'local_custompage_page_move_up' => [
    'classname'    => 'local_custompage\external\page\move_up',
    'description'  => 'Move a custom page up in sort order',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/custompage:edit',
  ],
  'local_custompage_page_move_down' => [
    'classname'    => 'local_custompage\external\page\move_down',
    'description'  => 'Move a custom page down in sort order',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/custompage:edit',
  ],
];
