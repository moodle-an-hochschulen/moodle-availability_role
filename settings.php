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
    // Supported roles heading.
    $name = 'availability_role/supportedrolesheading';
    $title = get_string('setting_supportedrolesheading', 'availability_role', null, true);
    $description = get_string('setting_supportedrolesheading_desc', 'availability_role', null, true) . '<br />' .
            get_string('setting_supportedrolesheading_note', 'availability_role', null, true);
    $setting = new admin_setting_heading($name, $title, $description);
    $settings->add($setting);

    // Create role chooser options for course, course category, and global levels.
    $allroles = get_all_roles();
    $systemcontext = context_system::instance();
    $allrolenames = role_fix_names($allroles, $systemcontext, ROLENAME_ORIGINAL);

    $courseroleoptions = [];
    $categoryroleoptions = [];
    $globalroleoptions = [];

    if (!empty($allrolenames)) {
        foreach ($allrolenames as $role) {
            $rolecontextlevels = get_role_contextlevels($role->id);
            $rolename = $role->localname;

            // Add to course roles if assignable at course level.
            if (in_array(CONTEXT_COURSE, $rolecontextlevels)) {
                $courseroleoptions[$role->id] = $rolename;
            }

            // Add to course category roles if assignable at course category level.
            if (in_array(CONTEXT_COURSECAT, $rolecontextlevels)) {
                $categoryroleoptions[$role->id] = $rolename;
            }

            // Add to global roles if assignable at system level.
            if (in_array(CONTEXT_SYSTEM, $rolecontextlevels)) {
                $globalroleoptions[$role->id] = $rolename;
            }
        }
    }

    // Setting for supported course roles.
    $name = 'availability_role/courseroles';
    $title = get_string('setting_courseroles', 'availability_role', null, true);
    $setting = new admin_setting_configmulticheckbox($name, $title, null, $courseroleoptions, $courseroleoptions);
    $settings->add($setting);

    // Setting for supported course category roles.
    $name = 'availability_role/coursecatroles';
    $title = get_string('setting_coursecatroles', 'availability_role', null, true);
    $setting = new admin_setting_configmulticheckbox($name, $title, null, [], $categoryroleoptions);
    $settings->add($setting);

    // Setting for supported global roles.
    $name = 'availability_role/globalroles';
    $title = get_string('setting_globalroles', 'availability_role', null, true);
    $setting = new admin_setting_configmulticheckbox($name, $title, null, [], $globalroleoptions);
    $settings->add($setting);

    // Special roles heading.
    $name = 'availability_role/specialrolesheading';
    $title = get_string('setting_specialrolesheading', 'availability_role', null, true);
    $setting = new admin_setting_heading($name, $title, null);
    $settings->add($setting);

    // Setting for guest role.
    $name = 'availability_role/supportguestrole';
    $title = get_string('setting_supportguestrole', 'availability_role', null, true);
    $description = get_string('setting_supportguestrole_desc', 'availability_role', null, true);
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);

    // Setting for not-logged-in role.
    $name = 'availability_role/supportnotloggedinrole';
    $title = get_string('setting_supportnotloggedinrole', 'availability_role', null, true);
    $description = get_string('setting_supportnotloggedinrole_desc', 'availability_role', null, true);
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);
}
