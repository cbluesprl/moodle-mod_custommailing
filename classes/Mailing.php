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
 * @package    mod_recalluser
 * @author     olivier@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_recalluser;

use dml_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once $CFG->dirroot . '/mod/recalluser/lib.php';

/**
 * Class Mailing
 * @package mod_recalluser
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

        return $DB->insert_record('recalluser_mailing', $record);
    }

    /**
     * @param int $id
     * @return stdClass
     * @throws dml_exception
     */
    public static function get(int $id) {
        global $DB;

        $record = $DB->get_record('recalluser_mailing', ['id' => $id], '*', MUST_EXIST);
        $record = Mailing::format($record);


        return $record;
    }

    /**
     * @param int $recalluser_id
     * @return stdClass[]
     * @throws dml_exception
     */
    public static function getAll(int $recalluser_id) {
        global $DB;

        $records = [];
        $rs = $DB->get_recordset('recalluser_mailing', ['recalluserid' => $recalluser_id], 'ORDER BY id');
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

        return $DB->update_record('recalluser_mailing', $record);
    }

    /**
     * @param int $id
     * @throws dml_exception
     */
    public static function delete(int $id) {
        global $DB;

        $DB->delete_records('recalluser_logs', ['recallusermailingid' => $id]);
        $DB->delete_records('recalluser_mailing', ['id' => $id]);
    }

    /**
     * @param int $recalluser_id
     * @throws dml_exception
     */
    public static function deleteAll(int $recalluser_id) {
        foreach (Mailing::getAll($recalluser_id) as $mailing) {
            Mailing::delete($mailing->id);
        }
    }

    /**
     * @param stdClass $record
     * @return stdClass
     */
    protected static function format(stdClass $record) {
        $record->id = (int) $record->id;
        $record->recalluserid = (int) $record->recalluserid;
        $record->mailingcontentformat = (int) $record->mailingcontentformat;
        $record->mailingmode = (int) $record->mailingmode;
        $record->mailingdelay = (int) $record->mailingdelay;
        $record->mailingstatus = (bool) $record->mailingstatus;
        $record->targetmoduleid = (int) $record->targetmoduleid;
        $record->targetmodulestatus = (int) $record->targetmodulestatus;
        $record->customcertmoduleid = empty($record->customcertmoduleid) ? null : (int) $record->customcertmoduleid;
        $record->starttime = (int) $record->starttime;
        $record->timecreated = (int) $record->timecreated;
        $record->timemodified = (int) $record->timemodified;

        return $record;
    }
}
