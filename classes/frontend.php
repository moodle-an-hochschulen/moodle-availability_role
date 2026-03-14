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
}
