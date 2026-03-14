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
 * Availability role - Upgrade steps
 *
 * @package    availability_role
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade availability_role
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_availability_role_upgrade($oldversion) {
    if ($oldversion < 2025041405) {
        // Rename the config setting 'setting_supportedroles' to 'courseroles' to align with the
        // naming of the other role list settings ('coursecatroles', 'globalroles').
        $oldvalue = get_config('availability_role', 'setting_supportedroles');
        if ($oldvalue !== false) {
            set_config('courseroles', $oldvalue, 'availability_role');
            unset_config('setting_supportedroles', 'availability_role');
        }

        // Rename 'setting_supportguestrole' to 'supportguestrole' to drop the redundant 'setting_' prefix.
        $oldvalue = get_config('availability_role', 'setting_supportguestrole');
        if ($oldvalue !== false) {
            set_config('supportguestrole', $oldvalue, 'availability_role');
            unset_config('setting_supportguestrole', 'availability_role');
        }

        // Rename 'setting_supportnotloggedinrole' to 'supportnotloggedinrole' for the same reason.
        $oldvalue = get_config('availability_role', 'setting_supportnotloggedinrole');
        if ($oldvalue !== false) {
            set_config('supportnotloggedinrole', $oldvalue, 'availability_role');
            unset_config('setting_supportnotloggedinrole', 'availability_role');
        }

        upgrade_plugin_savepoint(true, 2025041405, 'availability', 'role');
    }

    return true;
}
