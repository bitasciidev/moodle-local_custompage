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

namespace local_custompage\event;

use coding_exception;
use core\event\base;
use moodle_url;
use stdClass;
use core\context;

/**
 * Custom page viewed event class.
 *
 * @package     local_custompage
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string    name:      The name of the custom page
 * }
 */
class custompage_viewed extends base {
    /** @var string Event object table name. */
    public const TABLE = 'local_custompages';

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = self::TABLE;
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Creates an instance from a custom page object.
     *
     * @param stdClass $custompage The custom page object from the database.
     * @param \core\context $context The context.
     * @return self
     */
    public static function create_from_object(stdClass $custompage, \core\context $context): self {
        $eventparams = [
            'context'  => $context,
            'objectid' => $custompage->id,
            'other' => [
                'name'     => $custompage->name,
            ],
        ];
        $event = self::create($eventparams);
        $event->add_record_snapshot(self::TABLE, $custompage);
        return $event;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcustompageviewed', 'local_custompage');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the custom page with id '$this->objectid'.";
    }

    /**
     * Custom validations.
     *
     * @throws coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->objectid)) {
            throw new coding_exception('The \'objectid\' must be set.');
        }
    }

    /**
     * Returns relevant URL.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url('/local/custompage/view.php', ['id' => $this->objectid]);
    }
}
