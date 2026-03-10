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
 * Availability role - Tests for role restrictions
 *
 * @package    availability_role
 * @copyright  2015 Bence Laky, Synergy Learning UK <b.laky@intrallect.com>
 *             on behalf of Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_role;

/**
 * Availability role - Unit tests for the condition
 *
 * @package    availability_role
 * @copyright  2015 Bence Laky, Synergy Learning UK <b.laky@intrallect.com>
 *             on behalf of Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class condition_test extends \advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp(): void {
        global $CFG;

        // Load the mock info class so that it can be used.
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');

        // Call parent setup.
        parent::setUp();
    }

    /**
     * Tests constructing and using condition.
     *
     * @covers \availability_role\condition::is_available()
     * @covers \availability_role\condition::get_description()
     */
    public function test_usage_course_role(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Create a course and two users.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        // Get the editingteacher role (assignable at course context level).
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);

        // Enrol both users in the course.
        $generator->enrol_user($student->id, $course->id, $studentrole->id);
        $generator->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Create the condition structure and instance.
        $studentinfo = new \core_availability\mock_info($course, $student->id);
        $teacherinfo = new \core_availability\mock_info($course, $teacher->id);

        // Use the editingteacher role and course role type for the condition.
        $structure = (object)['type' => 'role', 'id' => (int) $teacherrole->id, 'typeid' => condition::ROLETYPE_COURSE];
        $cond = new condition($structure);

        // The condition should only be available for the teacher with the course role, not the student.
        $this->assertFalse($cond->is_available(false, $studentinfo, true, $student->id));
        $this->assertTrue($cond->is_available(false, $teacherinfo, true, $teacher->id));

        // Check the description string (positive and negated).
        $coursecontext = \context_course::instance($course->id);
        $rolename = role_get_name($teacherrole, $coursecontext);
        $information = $cond->get_description(false, false, $studentinfo);
        $this->assertEquals(get_string('requires_role', 'availability_role', $rolename), $information);
        $informationnot = $cond->get_description(false, true, $studentinfo);
        $this->assertEquals(get_string('requires_notrole', 'availability_role', $rolename), $informationnot);
    }

    /**
     * Tests the condition with a course category role (ROLETYPE_COURSECAT).
     *
     * @covers \availability_role\condition::is_available()
     * @covers \availability_role\condition::get_description()
     */
    public function test_usage_coursecat_role(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Create a course category, a course in that category, and two users.
        $generator = $this->getDataGenerator();
        $category = $generator->create_category();
        $course = $generator->create_course(['category' => $category->id]);
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        // Get the manager role (assignable at course category context level).
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        // Enrol both users in the course.
        $generator->enrol_user($student->id, $course->id);
        $generator->enrol_user($teacher->id, $course->id);

        // Assign teacher the manager role in the course category context.
        $catcontext = \context_coursecat::instance($category->id);
        role_assign($managerrole->id, $teacher->id, $catcontext->id);

        // Create the condition structure and instance.
        $studentinfo = new \core_availability\mock_info($course, $student->id);
        $teacherinfo = new \core_availability\mock_info($course, $teacher->id);

        // Use the manager role and course category type for the condition.
        $structure = (object)['type' => 'role', 'id' => (int) $managerrole->id, 'typeid' => condition::ROLETYPE_COURSECAT];
        $cond = new condition($structure);

        // The condition should only be available for the teacher with the course category role, not the student.
        $this->assertFalse($cond->is_available(false, $studentinfo, true, $student->id));
        $this->assertTrue($cond->is_available(false, $teacherinfo, true, $teacher->id));

        // Check the description string (positive and negated).
        $coursecontext = \context_course::instance($course->id);
        $rolename = role_get_name($managerrole, $coursecontext);
        $information = $cond->get_description(false, false, $studentinfo);
        $this->assertEquals(get_string('requires_role', 'availability_role', $rolename), $information);
        $informationnot = $cond->get_description(false, true, $studentinfo);
        $this->assertEquals(get_string('requires_notrole', 'availability_role', $rolename), $informationnot);
    }

    /**
     * Tests the condition with a global (system) role (ROLETYPE_GLOBAL).
     *
     * @covers \availability_role\condition::is_available()
     * @covers \availability_role\condition::get_description()
     */
    public function test_usage_global_role(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Create a course and two users.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        // Enrol both users in the course.
        $generator->enrol_user($student->id, $course->id);
        $generator->enrol_user($teacher->id, $course->id);

        // Get the manager role (assignable at system context level).
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        // Assign teacher the manager role in the system context.
        $systemcontext = \context_system::instance();
        role_assign($managerrole->id, $teacher->id, $systemcontext->id);

        // Create the condition structure and instance.
        $studentinfo = new \core_availability\mock_info($course, $student->id);
        $teacherinfo = new \core_availability\mock_info($course, $teacher->id);

        // Use the manager role and global type for the condition.
        $structure = (object)['type' => 'role', 'id' => (int) $managerrole->id, 'typeid' => condition::ROLETYPE_GLOBAL];
        $cond = new condition($structure);

        // The condition should only be available for the teacher with the global role, not the student.
        $this->assertFalse($cond->is_available(false, $studentinfo, true, $student->id));
        $this->assertTrue($cond->is_available(false, $teacherinfo, true, $teacher->id));

        // Check the description string (positive and negated).
        $coursecontext = \context_course::instance($course->id);
        $rolename = role_get_name($managerrole, $coursecontext);
        $information = $cond->get_description(false, false, $studentinfo);
        $this->assertEquals(get_string('requires_role', 'availability_role', $rolename), $information);
        $informationnot = $cond->get_description(false, true, $studentinfo);
        $this->assertEquals(get_string('requires_notrole', 'availability_role', $rolename), $informationnot);
    }

    /**
     * Tests the save() function with typeid for course roles (ROLETYPE_COURSE = 0).
     *
     * @covers \availability_role\condition::save()
     */
    public function test_save_typeid_course(): void {
        $structure = (object)['id' => 123, 'typeid' => condition::ROLETYPE_COURSE];
        $cond = new condition($structure);
        $structure->type = 'role';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the save() function with typeid for course category roles (ROLETYPE_COURSECAT = 1).
     *
     * @covers \availability_role\condition::save()
     */
    public function test_save_typeid_coursecat(): void {
        $structure = (object)['id' => 123, 'typeid' => condition::ROLETYPE_COURSECAT];
        $cond = new condition($structure);
        $structure->type = 'role';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the save() function with typeid for global roles (ROLETYPE_GLOBAL = 2).
     *
     * @covers \availability_role\condition::save()
     */
    public function test_save_typeid_global(): void {
        $structure = (object)['id' => 123, 'typeid' => condition::ROLETYPE_GLOBAL];
        $cond = new condition($structure);
        $structure->type = 'role';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests that a user accessing a course as guest matches the guest role condition.
     *
     * @covers \availability_role\condition::is_available()
     * @covers \availability_role\condition::get_description()
     */
    public function test_is_available_guest_role(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Create a course and two users.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $guestuser = $generator->create_user();
        $student = $generator->create_user();

        // Get the guest role.
        $guestrole = get_guest_role();
        $this->assertNotEmpty($guestrole, 'Guest role must exist');

        // Get the student role and enrol the student normally.
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $generator->enrol_user($student->id, $course->id, $studentrole->id);

        // Assign the guest role to the guest user in the course context (simulates guest access).
        $coursecontext = \context_course::instance($course->id);
        role_assign($guestrole->id, $guestuser->id, $coursecontext->id);

        // Create the condition structure and instance.
        $guestuserinfo = new \core_availability\mock_info($course, $guestuser->id);
        $studentinfo = new \core_availability\mock_info($course, $student->id);

        // Create condition for the guest role.
        $structure = (object)['type' => 'role', 'id' => (int) $guestrole->id, 'typeid' => condition::ROLETYPE_COURSE];
        $cond = new condition($structure);

        // The condition should be available for the guest user, but not for the regular student.
        $this->assertTrue($cond->is_available(false, $guestuserinfo, true, $guestuser->id));
        $this->assertFalse($cond->is_available(false, $studentinfo, true, $student->id));

        // Check the description string (positive and negated).
        $rolename = role_get_name($guestrole, $coursecontext);
        $information = $cond->get_description(false, false, $studentinfo);
        $this->assertEquals(get_string('requires_role', 'availability_role', $rolename), $information);
        $informationnot = $cond->get_description(false, true, $studentinfo);
        $this->assertEquals(get_string('requires_notrole', 'availability_role', $rolename), $informationnot);
    }

    /**
     * Tests that a not-logged-in user
        // matches the not-logged-in role condition.
     *
     * @covers \availability_role\condition::is_available()
     * @covers \availability_role\c
        // ondition::get_description()
     */
    public function test_is_available_notloggedin_role(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Create a course and a student.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();

        // Get the not-logged-in role from config.
        $notloggedinrole = $DB->get_record('role', ['id' => $CFG->notloggedinroleid], '*', MUST_EXIST);

        // Get the student role and enrol the student normally.
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $generator->enrol_user($student->id, $course->id, $studentrole->id);

        // Create the condition structure and instance.
        $notloggedininfo = new \core_availability\mock_info($course, 0);
        $studentinfo = new \core_availability\mock_info($course, $student->id);

        // Create condition for the not-logged-in role.
        $structure = (object)['type' => 'role', 'id' => (int) $notloggedinrole->id, 'typeid' => condition::ROLETYPE_COURSE];
        $cond = new condition($structure);

        // The condition should be available for the not-logged-in user (userid=0), but not for the regular student.
        $this->assertTrue($cond->is_available(false, $notloggedininfo, true, 0));
        $this->assertFalse($cond->is_available(false, $studentinfo, true, $student->id));

        // Check the description string (positive and negated).
        $coursecontext = \context_course::instance($course->id);
        $rolename = role_get_name($notloggedinrole, $coursecontext);
        $information = $cond->get_description(false, false, $studentinfo);
        $this->assertEquals(get_string('requires_role', 'availability_role', $rolename), $information);
        $informationnot = $cond->get_description(false, true, $studentinfo);
        $this->assertEquals(get_string('requires_notrole', 'availability_role', $rolename), $informationnot);
    }

    /**
     * Tests that negating the condition ($not=true) inverts the availability result.
     *
     * @covers \availability_role\condition::is_available()
     */
    public function test_is_available_negated(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Create a course and two users.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        // Get the editingteacher role (assignable at course context level).
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);

        // Enrol both users in the course.
        $generator->enrol_user($student->id, $course->id, $studentrole->id);
        $generator->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Create the condition structure and instance.
        $studentinfo = new \core_availability\mock_info($course, $student->id);
        $teacherinfo = new \core_availability\mock_info($course, $teacher->id);

        // Use the editingteacher role and course role type for the condition.
        $structure = (object)['type' => 'role', 'id' => (int) $teacherrole->id, 'typeid' => condition::ROLETYPE_COURSE];
        $cond = new condition($structure);

        // With $not=false: teacher matches, student does not.
        $this->assertTrue($cond->is_available(false, $teacherinfo, true, $teacher->id));
        $this->assertFalse($cond->is_available(false, $studentinfo, true, $student->id));

        // With $not=true: results must be inverted.
        $this->assertFalse($cond->is_available(true, $teacherinfo, true, $teacher->id));
        $this->assertTrue($cond->is_available(true, $studentinfo, true, $student->id));
    }

    /**
     * Tests that get_description() returns the missing role string when the role no longer exists in the database.
     *
     * @covers \availability_role\condition::get_description()
     */
    public function test_get_description_missing_role(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Create a course and a student.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();

        // Create a temporary role, build the condition, then delete the role.
        $roleid = create_role('Temporary Role', 'temporaryrole', 'A temporary role for testing');
        $structure = (object)['type' => 'role', 'id' => (int) $roleid, 'typeid' => condition::ROLETYPE_COURSE];
        $cond = new condition($structure);
        delete_role($roleid);

        // Create the info object.
        $studentinfo = new \core_availability\mock_info($course, $student->id);

        // The function get_description() should return the missing role string.
        $missing = get_string('missing', 'availability_role');
        $information = $cond->get_description(false, false, $studentinfo);
        $this->assertEquals(get_string('requires_role', 'availability_role', $missing), $information);
        $informationnot = $cond->get_description(false, true, $studentinfo);
        $this->assertEquals(get_string('requires_notrole', 'availability_role', $missing), $informationnot);
    }

    /**
     * Tests backward compatibility: old conditions without typeid should default to ROLETYPE_COURSE (0) when saved.
     *
     * @covers \availability_role\condition::__construct()
     * @covers \availability_role\condition::save()
     */
    public function test_save_backward_compat(): void {
        // Simulate an old condition without typeid.
        $structure = (object)['id' => 123];
        $cond = new condition($structure);
        $saved = $cond->save();
        $this->assertEquals(condition::ROLETYPE_COURSE, $saved->typeid);
        $this->assertEquals(123, $saved->id);
        $this->assertEquals('role', $saved->type);
    }
}
