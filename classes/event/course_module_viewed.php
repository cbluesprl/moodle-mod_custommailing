<?php
// This file is part of the Reengagement module for Moodle - http://moodle.org/
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
 * This file manages the course_module_viewed event
 *
 * @package    mod_custommailing
 * @author     jeanfrancois@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_custommailing\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course_module_viewed
 * @package mod_custommailing\event
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'custommailing';
        parent::init();
    }
}
