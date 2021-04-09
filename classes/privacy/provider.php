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
 * This file manages the privacy
 *
 * @package    mod_custommailing
 * @author     jeanfrancois@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_custommailing\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use context;
use context_module;
use core_privacy\local\request\writer;
use mod_custommailing\Mailing;
use mod_custommailing\MailingLog;

defined('MOODLE_INTERNAL') || die();

/**
 * Class provider
 * This plugin does not store any personal user data.
 *
 * @package mod_custommailing\privacy
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider
{

    /**
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'custommailing_logs',
            [
                'custommailingmailingid' => 'privacy:metadata:custommailingmailingid',
                'emailtouserid' => 'privacy:metadata:emailtouserid',
                'emailstatus' => 'privacy:metadata:emailstatus',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified'
            ],
            'privacy:metadata:custommailing_logs'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        return (new contextlist)->add_from_sql(
            "SELECT ctx.id
                 FROM {context} ctx
                 JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                 JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 JOIN {custommailing} c ON c.id = cm.instance
                 JOIN {custommailing_mailing} cmm ON cmm.custommailingid = c.id
                 JOIN {custommailing_logs} cml ON cml.custommailingmailingid = cmm.id
                 WHERE cml.emailtouserid = :userid",
            [
                'modname' => 'custommailing',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid
            ]
        );
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!is_a($context, \context_module::class)) {
            return;
        }
        $sql = "SELECT cml.emailtouserid as userid
                    FROM {custommailing_logs} cml
                    JOIN {custommailing_mailing} cmm ON cmm.id = cml.custommailingmailingid
                    JOIN {custommailing} c ON c.id = cmm.custommailingid
                    JOIN {course_modules} cm ON cm.instance = c.id
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'custommailing'
                 WHERE cm.id = :instanceid";

        $params = [
            'modulename' => 'custommailing',
            'instanceid'    => $context->instanceid,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(context $context) {

        if (!$context instanceof context_module) {
            return;
        }

        if (!$cm = get_coursemodule_from_id('custommailing', $context->instanceid)) {
            return;
        }

        Mailing::deleteAll($cm->instance);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {

        if (empty($contextlist->count())) {
            return;
        }
        $userid = (int) $contextlist->get_user()->id;

        foreach ($contextlist as $context) {
            if (!$context instanceof context_module) {
                continue;
            }
            if (!$cm = get_coursemodule_from_id('custommailing', $context->instanceid)) {
                continue;
            }
            MailingLog::deleteByUser($userid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        $context = $userlist->get_context();
        if (!is_a($context, \context_module::class)) {
            return;
        }
        if (!$cm = get_coursemodule_from_id('custommailing', $context->instanceid)) {
            return;
        }
        $userids = $userlist->get_userids();
        foreach ($userids as $userid) {
            MailingLog::deleteByUser($userid);
        }

    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
       global $DB;

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $contextparams['userid'] = $user->id;

        $sql = "SELECT cml.*,ctx.id as contextid 
                FROM {custommailing_logs} cml
                    JOIN {custommailing_mailing} cmm ON cmm.id = cml.custommailingmailingid
                    JOIN {custommailing} c ON c.id = cmm.custommailingid
                    JOIN {course_modules} cm ON cm.instance = c.id
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'custommailing'
                    JOIN {context} ctx ON ctx.instanceid = cm.id
                WHERE cml.emailtouserid = :userid AND ctx.id {$contextsql}";

        $logs = $DB->get_records_sql($sql, $contextparams);

        foreach ($logs as $log) {
            $logdata = (object) [
                'custommailingmailingid' => $log->custommailingmailingid,
                'emailtouserid' => $log->emailtouserid,
                'emailstatus' => $log->emailstatus,
                'timecreated' => transform::datetime($log->timecreated),
                'timemodified' => transform::datetime($log->timemodified)
            ];
            $context = context::instance_by_id($log->contextid);
            writer::with_context($context)->export_data(
                [get_string('modulename', 'custommailing') . ' ' . $log->custommailingmailingid],
                $logdata
            );
        }
    }
}
