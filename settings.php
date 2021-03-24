<?php
// This file is part of the custom mailing module for Moodle - http://moodle.org/
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
 * Creates a link to the upload form on the settings page.
 *
 * @package    mod_custommailing
 * @author     jeanfrancois@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('modsettings', new admin_category('custommailing', get_string('pluginname', 'mod_custommailing')));
$settings = new admin_settingpage('modsettingcustommailing', new lang_string('settings', 'mod_custommailing'));

$yesnooptions = [
    0 => get_string('no'),
    1 => get_string('yes'),
];

$settings->add(new admin_setting_configselect('custommailing/debugmode',
    get_string('debugmode', 'mod_custommailing'), get_string('debugmode_help', 'mod_custommailing'), 0, $yesnooptions));

$ADMIN->add('custommailing', $settings);

// Tell core we already added the settings structure.
$settings = null;
