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
 * Mailing Class
 *
 * @package    mod_custommailing
 * @author     olivier@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_custommailing;

use dml_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot . '/mod/custommailing/lib.php';

/**
 * Class Mailing
 * @package mod_custommailing
 *
 * @property int id
 * @property int custommailingid
 * @property string mailingname
 * @property string mailinglang
 * @property string mailingsubject
 * @property string mailingcontent
 * @property int mailingcontentformat
 * @property int mailingmodle
 * @property int mailingdelay
 * @property int mailingstatus
 * @property int targetmoduleid
 * @property int customcertmoduleid
 * @property int starttime
 * @property int timecreated
 * @property int timemodified
 */
class Mailing {

    /**
     * @param stdClass $record
     * @return bool|int
     * @throws dml_exception
     */
    public static function create(stdClass $record) {
        global $DB;

        $record->timecreated = $record->timemodified = time();

        return $DB->insert_record('custommailing_mailing', $record);
    }

    /**
     * @param int $id
     * @return stdClass
     * @throws dml_exception
     */
    public static function get(int $id) {
        global $DB;

        $record = $DB->get_record('custommailing_mailing', ['id' => $id], '*', MUST_EXIST);
        $record = Mailing::format($record);


        return $record;
    }

    /**
     * @param int $id
     * @return stdClass
     * @throws dml_exception
     */
    public static function getWithCourse(int $id) {
        global $DB;

        $sql = "SELECT cm.*, c.course as courseid
                FROM {custommailing_mailing} cm
                JOIN {custommailing} c ON c.id = cm.custommailingid
                ";
        $record = $DB->get_record_sql($sql, ['id' => $id]);
        $record = Mailing::format($record);

        return $record;
    }


    /**
     * @param int $custommailing_id
     * @return stdClass[]
     * @throws dml_exception
     */
    public static function getAll(int $custommailing_id) {
        global $DB;

        $records = [];
        $rs = $DB->get_recordset('custommailing_mailing', ['custommailingid' => $custommailing_id], 'id ASC');
        foreach ($rs as $record) {
            $record = Mailing::format($record);
            $records[$record->id] = $record;
        }
        $rs->close();

        return $records;
    }

    /**
     * @param stdClass $record
     * @return bool
     * @throws dml_exception
     */
    public static function update(stdClass $record) {
        global $DB;

        $record->timemodified = time();

        return $DB->update_record('custommailing_mailing', $record);
    }

    /**
     * @param int $id
     * @throws dml_exception
     */
    public static function delete(int $id) {
        global $DB;

        $DB->delete_records('custommailing_logs', ['custommailingmailingid' => $id]);
        $DB->delete_records('custommailing_mailing', ['id' => $id]);
    }

    /**
     * @param int $custommailing_id
     * @throws dml_exception
     */
    public static function deleteAll(int $custommailing_id) {
        foreach (Mailing::getAll($custommailing_id) as $mailing) {
            Mailing::delete($mailing->id);
        }
    }

    /**
     * @return array
     * @throws dml_exception
     */
    public static function getAllToSend() {
        global $DB;

        $records = [];
        $rs = $DB->get_recordset_sql(
            "SELECT cm.*, c.course as courseid
            FROM {custommailing_mailing} cm
            JOIN {custommailing} c ON c.id = cm.custommailingid
            WHERE cm.mailingstatus = :mailingstatus",
            ['mailingstatus' => MAILING_STATUS_ENABLED]
        );
        foreach ($rs as $record) {
            $record = Mailing::format($record);
            $records[$record->id] = $record;
        }
        $rs->close();

        return $records;
    }

    /**
     * @param stdClass $record
     * @return stdClass
     */
    protected static function format(stdClass $record) {
        $record->id = (int) $record->id;
        $record->custommailingid = (int) $record->custommailingid;
        $record->mailingcontentformat = (int) $record->mailingcontentformat;
        $record->mailingmode = (int) $record->mailingmode;
        $record->mailingdelay = (int) $record->mailingdelay;
        $record->mailingstatus = (bool) $record->mailingstatus;
        $record->retroactive = (bool) $record->retroactive;
        $record->targetmoduleid = (int) $record->targetmoduleid;
        $record->targetmodulestatus = (int) $record->targetmodulestatus;
        $record->customcertmoduleid = empty($record->customcertmoduleid) ? null : (int) $record->customcertmoduleid;
        $record->starttime = (int) $record->starttime;
        $record->timecreated = (int) $record->timecreated;
        $record->timemodified = (int) $record->timemodified;

        return $record;
    }
}
