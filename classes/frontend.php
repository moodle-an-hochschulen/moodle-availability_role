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
 * Availability role - Frontend form
 *
 * @package    availability_role
 * @copyright  2015 Bence Laky, Synergy Learning UK <b.laky@intrallect.com>
 *             on behalf of Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_role;

/**
 * Availability role - Frontend form class
 *
 * @package    availability_role
 * @copyright  2015 Bence Laky, Synergy Learning UK <b.laky@intrallect.com>
 *             on behalf of Alexander Bias Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Get the initial parameters needed for JavaScript.
     *
     * @param \stdClass          $course
     * @param \cm_info|null      $cm
     * @param \section_info|null $section
     *
     * @return array
     */
    protected function get_javascript_init_params($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        // Init the JS array to be returned.
        $jsarray = [];

        // Get all roles for course.
        $coursecontext = \context_course::instance($course->id);
        foreach ($this->get_course_roles() as $rec) {
            $jsarray[] = (object)[
                'id' => $rec->id,
                'name' => role_get_name($rec, $coursecontext),
                'type' => get_string('course'),
                'typeid' => \availability_role\condition::ROLETYPE_COURSE,
            ];
        }

        // Get all roles for course category.
        $catcontext = \context_coursecat::instance($course->category);
        foreach ($this->get_coursecat_roles() as $rec) {
            $jsarray[] = (object)[
                'id' => $rec->id,
                'name' => role_get_name($rec, $catcontext),
                'type' => get_string('coursecategory'),
                'typeid' => \availability_role\condition::ROLETYPE_COURSECAT,
            ];
        }

        // Get all global roles.
        $systemcontext = \context_system::instance();
        foreach ($this->get_global_roles() as $rec) {
            $jsarray[] = (object)[
                'id' => $rec->id,
                'name' => role_get_name($rec, $systemcontext),
                'type' => get_string('coresystem'),
                'typeid' => \availability_role\condition::ROLETYPE_GLOBAL,
            ];
        }

        // If restricting an activity module, mark roles that cannot view this module type as nonsensical.
        // A restriction is nonsensical if the role has no CAP_ALLOW for mod/{modname}:view anywhere in the
        // context chain, meaning users with only that role can never access the activity regardless.
        // When editing an existing activity, $cm carries the module name. When adding a new activity,
        // $cm is null but the module name is available via the 'add' request parameter (modedit.php?add=forum).
        $modname = null;
        if ($cm !== null) {
            $modname = $cm->modname;
        } else {
            $addparam = optional_param('add', '', PARAM_ALPHANUM);
            if (!empty($addparam)) {
                $modname = $addparam;
            }
        }

        // If we have a module name.
        if ($modname !== null) {
            // Build the capability name and check it exists.
            // If the module has no view capability, we skip the nonsensical restriction.
            $modviewcap = 'mod/' . $modname . ':view';
            if (get_capability_info($modviewcap) !== null) {
                // A role restriction is nonsensical when the role can never view this activity type.
                //
                // Strategy:
                // 1. Check the 'user' archetype (= authenticated user, the implicit base for every
                // logged-in course member). If it has CAP_ALLOW for the view capability, then all
                // standard enrolled-user roles inherit that access by default – unless explicitly
                // overridden at course/category level.
                // 2. Layer explicit role_capabilities overrides (CAP_ALLOW / CAP_PROHIBIT) from the
                // context chain on top to catch local permission changes.
                // 3. Custom roles (no archetype) are not backed by the authenticated-user default,
                // so they are nonsensical unless they carry an explicit CAP_ALLOW.

                // Step 1: Get the default capabilities for the 'user' archetype and check
                // if it has CAP_ALLOW for the module view capability.
                $authuserdefaults = get_default_capabilities('user');
                $authuserhasview = isset($authuserdefaults[$modviewcap]) && $authuserdefaults[$modviewcap] == CAP_ALLOW;

                // Step 2: Get explicit CAP_ALLOW / CAP_PROHIBIT for the view capability in the course context
                // and cache them in arrays of roleid => capvalue.
                [$explicitallowed, $explicitprohibited] = get_roles_with_cap_in_context($coursecontext, $modviewcap);

                // Step 3: Loop through the roles and mark them as nonsensical if they have no access to the activity.
                foreach ($jsarray as $role) {
                    if (isset($explicitprohibited[$role->id])) {
                        // Explicit CAP_PROHIBIT somewhere in the context chain → no access.
                        $role->nonsensical = true;
                    } else if (isset($explicitallowed[$role->id])) {
                        // Explicit CAP_ALLOW override → access guaranteed.
                        $role->nonsensical = false;
                    } else if ($authuserhasview && !empty($role->archetype)) {
                        // Authenticated users have the capability by default and this is a named
                        // archetype role → inherits that default access → not nonsensical.
                        $role->nonsensical = false;
                    } else {
                        // Custom role (no archetype) without an explicit CAP_ALLOW, or a named
                        // archetype role where the authenticated user base does not have the
                        // capability → nonsensical.
                        $role->nonsensical = true;
                    }
                }
            }
        }

        return [$jsarray];
    }

    /**
     * Get the configured course roles, including the guest role if enabled.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_course_roles() {
        global $DB;

        // Get the roles which have been enabled in the settings.
        $roleids = [];
        $enabledroles = get_config('availability_role', 'courseroles');
        if (!empty($enabledroles)) {
            $roleids = explode(',', $enabledroles);
        }

        // Add guest role, if desired and guest role exists and is not yet included.
        $guestroleid = get_guest_role()->id;
        if (
            get_config('availability_role', 'supportguestrole') &&
                !empty($guestroleid) &&
                !in_array($guestroleid, $roleids)
        ) {
            $roleids[] = $guestroleid;
        }

        return $DB->get_records_list('role', 'id', $roleids, 'sortorder');
    }

    /**
     * Get the configured course category roles.
     *
     * @return array
     * @throws \dml_exception
     */
    protected function get_coursecat_roles() {
        global $DB;

        // Get the roles which have been enabled in the settings.
        $roleids = [];
        $enabledroles = get_config('availability_role', 'coursecatroles');
        if (!empty($enabledroles)) {
            $roleids = explode(',', $enabledroles);
        }

        return $DB->get_records_list('role', 'id', $roleids, 'sortorder');
    }

    /**
     * Get the configured global roles, including the not-logged-in role if enabled.
     *
     * @return array
     * @throws \dml_exception
     */
    protected function get_global_roles() {
        global $DB, $CFG;

        // Get the roles which have been enabled in the settings.
        $roleids = [];
        $enabledroles = get_config('availability_role', 'globalroles');
        if (!empty($enabledroles)) {
            $roleids = explode(',', $enabledroles);
        }

        // Add role for users that are not logged in, if desired and this role exists and is not yet included.
        $notloggedinroleid = $CFG->notloggedinroleid;
        if (
            get_config('availability_role', 'supportnotloggedinrole') &&
                !empty($notloggedinroleid) &&
                !in_array($notloggedinroleid, $roleids)
        ) {
            $roleids[] = $notloggedinroleid;
        }

        return $DB->get_records_list('role', 'id', $roleids, 'sortorder');
    }

    /**
     * Decides whether this plugin should be available in a given course. The plugin can do this depending on course or
     * system settings. Default returns true.
     *
     * @param \stdClass          $course
     * @param \cm_info|null      $cm
     * @param \section_info|null $section
     *
     * @return bool
     */
    protected function allow_add($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        if ($cm) {
            $context = $cm->context;
        } else {
            $context = \context_course::instance($course->id);
        }

        if (!has_capability('availability/role:addinstance', $context)) {
            return false;
        }

        return true;
    }

    /**
     * Get javascript strings.
     * @return array
     */
    protected function get_javascript_strings() {
        return ['error_selectrole', 'nonsensical_warning'];
    }
}
