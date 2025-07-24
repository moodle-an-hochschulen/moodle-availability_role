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
 * Availability role - Settings file
 *
 * @package    availability_role
 * @copyright  2017 David Knuplesch, Ulm University <david.knuplesch@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Settings title to group preset related settings together with a common heading. We don't want a description here.
    $name = 'availability_role/setting_supportedrolesheading';
    $title = get_string('setting_supportedrolesheading', 'availability_role', null, true);
    $setting = new admin_setting_heading($name, $title, null);
    $settings->add($setting);

    // Create role chooser options.
    $roleoptions = [];
    $roles = get_all_roles();
    $systemcontext = context_system::instance();
    $rolenames = role_fix_names($roles, $systemcontext, ROLENAME_ORIGINAL);
    if (!empty($rolenames)) {
        foreach ($rolenames as $key => $role) {
            // If the role cannot be assigned in the course context, skip it.
            $rolecontextlevels = get_role_contextlevels($role->id);
            if (!in_array(CONTEXT_COURSE, $rolecontextlevels)) {
                continue;
            }

            // If the role is not already in the list, add it.
            if (!array_key_exists($role->id, $roleoptions)) {
                $roleoptions[$role->id] = $role->localname;
            }
        }
    }

    // Setting for supported roles.
    $name = 'availability_role/setting_supportedroles';
    $title = get_string('setting_supportedroles', 'availability_role', null, true);
    $description = get_string('setting_supportedroles_desc', 'availability_role', null, true).'<br />'.
            get_string('setting_supportedroles_note', 'availability_role', null, true);
    $setting = new admin_setting_configmulticheckbox($name, $title, $description, $roleoptions, $roleoptions);
    $settings->add($setting);

    // Setting for guest role.
    $name = 'availability_role/setting_supportguestrole';
    $title = get_string('setting_supportguestrole', 'availability_role', null, true);
    $description = get_string('setting_supportguestrole_desc', 'availability_role', null, true);
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);

    // Setting for not-logged-in role.
    $name = 'availability_role/setting_supportnotloggedinrole';
    $title = get_string('setting_supportnotloggedinrole', 'availability_role', null, true);
    $description = get_string('setting_supportnotloggedinrole_desc', 'availability_role', null, true);
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);

    // Course category roles.
    $sql = "SELECT r.*
                FROM {role} AS r, {role_context_levels} AS rcl
                WHERE r.id=rcl.roleid
                    AND rcl.contextlevel = ?
                ORDER BY r.name ASC";
    $roles = $DB->get_records_sql($sql, array(CONTEXT_COURSECAT));
    $options = array();
    foreach ($roles as $role) {
        $options[$role->id] = (!empty($role->name) ? $role->name : $role->shortname);
    }

    $settings->add(
        new admin_setting_configmultiselect(
            'availability_role/coursecatroles',
            get_string('setting_coursecatroles', 'availability_role'),
            get_string('setting_coursecatroles:description', 'availability_role'),
            get_config('availability_role', 'coursecatroles'),
            $options
        )
    );

    // Global roles.
    $sql = "SELECT r.*
                FROM {role} AS r, {role_context_levels} AS rcl
                WHERE r.id=rcl.roleid
                    AND rcl.contextlevel = ?
                ORDER BY r.name ASC";
    $roles = $DB->get_records_sql($sql, array(CONTEXT_SYSTEM));
    $options = array();
    foreach ($roles as $role) {
        $options[$role->id] = (!empty($role->name) ? $role->name : $role->shortname);
    }

    $settings->add(
        new admin_setting_configmultiselect(
            'availability_role/globalroles',
            get_string('setting_globalroles', 'availability_role'),
            get_string('setting_globalroles:description', 'availability_role'),
            get_config('availability_role', 'globalroles'),
            $options
        )
    );
}
