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
 * MailingLog Class
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
 */
class MailingLog
{
    /**
     * @param stdClass $record
     * @return bool|int
     * @throws dml_exception
     */
    public static function create(stdClass $record) {
        global $DB;

        $record->timecreated = $record->timemodified = time();

        return $DB->insert_record('custommailing_logs', $record);
    }

    /**
     * @param null|array $conditions
     * @return array
     * @throws dml_exception
     */
    public static function getAll($conditions = null) {
        global $DB;

        $records = [];
        $rs = $DB->get_recordset('custommailing_logs', $conditions, 'ORDER BY id');
        foreach ($rs as $record) {
            $record = MailingLog::format($record);
            $records[$record->id] = $record;
        }
        $rs->close();

        return $records;
    }

    /**
     * @param int $custommailingid
     * @return array
     * @throws dml_exception
     */
    public static function getAllForTable(int $custommailingid) {
        global $DB;

        $user_name_fields = get_all_user_name_fields(true, 'u',null,'user_');
        $records = [];
        $sql = "SELECT cl.id, cm.mailingname, u.id AS user_id, $user_name_fields, u.email AS user_email, cl.timecreated, cl.emailstatus 
                FROM {custommailing_logs} cl
                JOIN {custommailing_mailing} cm ON cm.id = cl.custommailingmailingid
                JOIN {user} u ON u.id = cl.emailtouserid
                WHERE cm.custommailingid = :custommailingid
                ORDER BY cl.id DESC";
        $rs = $DB->get_recordset_sql($sql, ['custommailingid' => $custommailingid]);
        foreach ($rs as $record) {
            $record = (array) $record;
            $user = new stdClass();
            $log = new stdClass();
            foreach ($record as $property => $value) {
                if (preg_match('/^user\_/', $property)) {
                    $property = preg_replace('/^user\_/','',$property);
                    $user->$property = $value;
                }
                else {
                    $log->$property = $value;
                }
            }
            $log->user = fullname($user) . ' - '. $user->email;
            $records[$log->id] = $log;
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

        return $DB->update_record('custommailing_logs', $record);
    }

    /**
     * @param int $userid
     * @throws dml_exception
     */
    public static function deleteByUser(int $userid) {
        global $DB;

        $DB->delete_records('custommailing_logs', ['emailtouserid' => $userid]);
    }

    /**
     * @param stdClass $record
     * @return stdClass
     */
    protected static function format(stdClass $record) {
        $record->id = (int) $record->id;
        $record->custommailingmailingid = (int) $record->custommailingmailingid;
        $record->emailtouserid = (int) $record->emailtouserid;
        $record->emailstatus = (int) $record->emailstatus;
        $record->timecreated = (int) $record->timecreated;
        $record->timemodified = (int) $record->timemodified;

        return $record;
    }
}
