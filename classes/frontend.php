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
        global $CFG;

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
        // The site course (front page) is not located in any course category and has $course->category == 0.
        // In that case there is no course category context, so we skip the course category roles altogether
        // to avoid instantiating an invalid context (which would throw a dml_missing_record_exception).
        if (!empty($course->category)) {
            $catcontext = \context_coursecat::instance($course->category);
            foreach ($this->get_coursecat_roles() as $rec) {
                $jsarray[] = (object)[
                    'id' => $rec->id,
                    'name' => role_get_name($rec, $catcontext),
                    'type' => get_string('coursecategory'),
                    'typeid' => \availability_role\condition::ROLETYPE_COURSECAT,
                ];
            }
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
                // A role restriction is nonsensical when a user who holds the role can never view this activity type.
                //
                // Moodle resolves a user's permission for a capability from all of the user's roles together:
                // A CAP_PROHIBIT in any role wins, otherwise a CAP_ALLOW in any role is enough. Every logged-in user
                // holds the authenticated user role (defaultuserroleid) in addition to the restricted role, so the
                // restricted role does not need to carry the view capability itself as long as the authenticated user
                // role does. This is the case for most resources (page, label, url, ...) whose view capability is only
                // defined for the 'user' archetype. Standard roles and custom roles are treated alike here, which avoids
                // false warnings for roles which simply inherit the capability.
                //
                // The guest role and the not-logged-in role are the exception: The guest account and not-logged-in
                // visitors do not hold the authenticated user role, so for these two roles only the role itself counts.
                //
                // role_context_capabilities() resolves a role's own permission along the context chain of the course
                // (overrides included), with a CAP_PROHIBIT on any level winning.

                // Resolve the permission of the authenticated user role in the course context.
                $authuserperm = CAP_INHERIT;
                if (!empty($CFG->defaultuserroleid)) {
                    $authusercaps = role_context_capabilities($CFG->defaultuserroleid, $coursecontext, $modviewcap);
                    $authuserperm = $authusercaps[$modviewcap] ?? CAP_INHERIT;
                }

                // Get the guest role id to be able to recognize the guest role in the loop.
                $guestroleid = get_guest_role()->id;

                // Loop through the roles and mark them as nonsensical if users with the role cannot view the activity.
                foreach ($jsarray as $role) {
                    $rolecaps = role_context_capabilities($role->id, $coursecontext, $modviewcap);
                    $roleperm = $rolecaps[$modviewcap] ?? CAP_INHERIT;

                    // Guests and not-logged-in users do not hold the authenticated user role.
                    $isspecialrole = ($role->id == $guestroleid || $role->id == $CFG->notloggedinroleid);
                    $baseperm = $isspecialrole ? CAP_INHERIT : $authuserperm;

                    if ($roleperm == CAP_PROHIBIT || $baseperm == CAP_PROHIBIT) {
                        // Explicit CAP_PROHIBIT somewhere in the context chain → no access at all.
                        $role->nonsensical = true;
                    } else if ($roleperm == CAP_ALLOW || $baseperm == CAP_ALLOW) {
                        // Either the role itself or the authenticated user role grants the capability → access.
                        $role->nonsensical = false;
                    } else {
                        // Neither the role nor the authenticated user role grants the capability → no access.
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
