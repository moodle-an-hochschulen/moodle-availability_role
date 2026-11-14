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
 * Availability role - Condition
 *
 * @package    availability_role
 * @copyright  2015 Bence Laky, Synergy Learning UK <b.laky@intrallect.com>
 *             on behalf of Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_role;

/**
 * Availability role - Condition class
 *
 * @package    availability_role
 * @copyright  2015 Bence Laky, Synergy Learning UK <b.laky@intrallect.com>
 *             on behalf of Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var int ID of role that this condition requires. */
    protected $roleid = 0;
    /** @var int ID of type that this condition requires. */
    protected $typeid = 0;

    /** @var int Role type for course context. */
    public const ROLETYPE_COURSE = 0;
    /** @var int Role type for course category context. */
    public const ROLETYPE_COURSECAT = 1;
    /** @var int Role type for system context. */
    public const ROLETYPE_GLOBAL = 2;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get role id.
        if (isset($structure->id) && is_int($structure->id)) {
            $this->roleid = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for role condition');
        }

        // Get type id.
        if (isset($structure->typeid) && is_int($structure->typeid)) {
            $this->typeid = $structure->typeid;
        } else {
            // Do not throw an exception if typeid is missing, as it was not required in
            // previous versions and we want to be able to handle old conditions without typeid as well.
            // Just assume it's course role in this case.
            $this->typeid = self::ROLETYPE_COURSE;
        }
    }

    /**
     * Save.
     *
     * @return object|\stdClass $result
     */
    public function save() {
        return (object)['type' => 'role', 'id' => $this->roleid, 'typeid' => $this->typeid];
    }

    /**
     * Adding the availability to restored course items.
     *
     * @param string       $restoreid
     * @param int          $courseid
     * @param \base_logger $logger
     * @param string       $name
     *
     * @return bool
     * @throws \dml_exception
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        global $DB;
        if (!$this->roleid) {
            // Without a role id, there is nothing to remap.
            return false;
        }
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'role', $this->roleid);
        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just
            // use the existing one.
            if ($DB->record_exists('role', ['id' => $this->roleid])) {
                return false;
            }
            // Otherwise it's a warning.
            $roletypenames = [
                self::ROLETYPE_COURSE    => 'course',
                self::ROLETYPE_COURSECAT => 'category',
                self::ROLETYPE_GLOBAL    => 'global',
            ];
            $roletypename = $roletypenames[$this->typeid] ?? 'unknown';
            $this->roleid = -1;
            $logger->process(
                'Restored item (' . $name . ') has availability condition on a ' . $roletypename .
                    ' role that was not restored',
                \backup::LOG_WARNING
            );
        } else {
            $this->roleid = (int)$rec->newitemid;
        }

        return true;
    }

    /**
     * Check if the item is available with this restriction.
     *
     * @param bool                    $not
     * @param \core_availability\info $info
     * @param bool                    $grabthelot
     * @param int                     $userid
     *
     * @return bool
     * @throws \coding_exception
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $USER, $CFG;
        $allow = false;

        // Check the user's role based on the type of role this condition is using.
        switch ($this->typeid) {
            case self::ROLETYPE_GLOBAL:
                $context = \context_system::instance();
                // Check all of the user's global roles.
                $allow = $this->user_has_role($context, $userid);
                break;
            case self::ROLETYPE_COURSECAT:
                // The site course (front page) is not located in any course category and has category == 0.
                // In that case there is no course category context to check against (and instantiating one would
                // throw a dml_missing_record_exception), so the user cannot hold a course category role and the
                // restriction simply does not grant access.
                if (!empty($info->get_course()->category)) {
                    $context = \context_coursecat::instance($info->get_course()->category);
                    // Check all of the user's course category roles.
                    $allow = $this->user_has_role($context, $userid);
                }
                break;
            case self::ROLETYPE_COURSE:
            default:
                $context = \context_course::instance($info->get_course()->id);
                // Is the user's course role switched?
                // Switched roles are stored in the session of the current user only, so they must only be honoured
                // if the availability is evaluated for the current user and not for any other user.
                if ($userid == $USER->id && !empty($USER->access['rsw'][$context->path])) {
                    // Check only switched role.
                    if ($USER->access['rsw'][$context->path] == $this->roleid) {
                        $allow = true;
                    }
                    // Or is the user currently having his own role(s)?
                } else {
                    // Check all of the user's course roles.
                    $allow = $this->user_has_role($context, $userid);
                }
                break;
        }

        // As get_user_roles only returns roles for enrolled users, we have to check whether a user
        // is viewing the course as guest or is not logged in separately.
        // These checks are skipped as soon as the access is already granted.

        // Is the user not logged in?
        if (!$allow && $CFG->notloggedinroleid == $this->roleid) {
            if (empty($userid) || isguestuser($userid)) {
                $allow = true;
            }
        }

        // Is the user viewing the course as guest?
        // The role is compared first as this is cheap and as it spares the is_guest() check
        // for all conditions which do not require the guest role.
        if (!$allow && $this->get_guest_roleid() == $this->roleid) {
            $context = \context_course::instance($info->get_course()->id);
            if (is_guest($context, $userid)) {
                $allow = true;
            }
        }

        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Check if the given user holds the role of this condition in the given context (or in one of its parent contexts).
     *
     * The role assignments are cached for the duration of the request in web requests, as many conditions
     * are evaluated in a row there (e.g. on the course page).
     *
     * @param \context $context
     * @param int      $userid
     *
     * @return bool
     */
    protected function user_has_role(\context $context, $userid): bool {
        // There are two cases where the user's roles must not be cached:
        // 1. On CLI: A CLI process (e.g. cron) may run for a long time and processes many tasks in a row without
        // the request cache being purged in between. Role changes which are done by other processes in the
        // meantime would be missed and the condition would be evaluated based on outdated role assignments.
        // 2. Without a user ID: In this case, get_user_roles() falls back to the current user. The cache key
        // would not be bound to a particular user then and would return the roles of the wrong user
        // if the current user changes within the request (e.g. during login or login as).
        $usecache = !CLI_SCRIPT && !empty($userid);

        // Try to get the user's role IDs from the request cache.
        $roleids = false;
        if ($usecache) {
            $cache = \core_cache\cache::make_from_params(\core_cache\store::MODE_REQUEST, 'availability_role', 'userroles');
            $key = $userid . '_' . $context->id;
            $roleids = $cache->get($key);
        }

        // If they are not cached (yet), get them from the database.
        if ($roleids === false) {
            $roleids = array_map('intval', array_column(get_user_roles($context, $userid), 'roleid'));
            if ($usecache) {
                $cache->set($key, $roleids);
            }
        }

        return in_array((int)$this->roleid, $roleids, true);
    }

    /**
     * Get the ID of the guest role.
     *
     * The ID is taken from the site configuration if possible, as get_guest_role() would fetch
     * the whole role record from the database on each call.
     *
     * @return int The ID of the guest role or 0 if there is no guest role.
     */
    protected function get_guest_roleid(): int {
        global $CFG;

        if (!empty($CFG->guestroleid)) {
            return (int)$CFG->guestroleid;
        }

        $guestrole = get_guest_role();
        return $guestrole ? (int)$guestrole->id : 0;
    }

    /**
     * Retrieve the description for the restriction.
     *
     * @param bool                    $full
     * @param bool                    $not
     * @param \core_availability\info $info
     *
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;
        $context = \context_course::instance($info->get_course()->id);
        $role = $DB->get_record('role', ['id' => $this->roleid]);
        if (!$role) {
            $missing = get_string('missing', 'availability_role');
            return get_string($not ? 'requires_notrole' : 'requires_role', 'availability_role', $missing);
        } else {
            $name = role_get_name($role, $context);
            return get_string($not ? 'requires_notrole' : 'requires_role', 'availability_role', $name);
        }
    }

    /**
     * Retrieve debugging string.
     *
     * @return string
     */
    public function get_debug_string() {
        return "Role id: $this->roleid";
    }
}
