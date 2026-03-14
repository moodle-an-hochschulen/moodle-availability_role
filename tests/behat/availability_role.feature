@availability @availability_role @javascript
Feature: availability_role
  In order to control student access to activities
  As a teacher
  I need to set role conditions which prevent student access

  Background:
    Given the following "categories" exist:
      | name          | idnumber |
      | Test Category | TC       |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion | category |
      | Course 1 | C1        | topics | 1                | TC       |
    And the following "roles" exist:
      | name          | shortname    | description              |
      | Course Tester | coursetester | Custom course role       |
      | Cat Tester    | cattester    | Custom category role     |
      | Global Tester | globaltester | Custom global role       |
    And the following "users" exist:
      | username       |
      | teacher1       |
      | student1       |
      | student2       |
      | manager1       |
      | courseroleuser |
      | catroleuser    |
      | globalroleuser |
      | noroleuser     |
    And the following "system role assigns" exist:
      | user           | role         | contextlevel | reference |
      | manager1       | manager      | System       |           |
      | globalroleuser | globaltester | System       |           |
    And the following "role assigns" exist:
      | user        | role      | contextlevel | reference |
      | catroleuser | cattester | Category     | TC        |
    And the following "course enrolments" exist:
      | user           | course | role           |
      | teacher1       | C1     | editingteacher |
      | student1       | C1     | student        |
      | courseroleuser | C1     | coursetester   |
      | catroleuser    | C1     | student        |
      | globalroleuser | C1     | student        |
      | noroleuser     | C1     | student        |
    And I log in as "admin"
    # Enable all three roles to be used in the availability condition.
    And I navigate to "Plugins > Availability restrictions > Restriction by role" in site administration
    And I click on "Course Tester" "checkbox" in the "#admin-courseroles" "css_element"
    And I click on "Cat Tester" "checkbox" in the "#admin-coursecatroles" "css_element"
    And I click on "Global Tester" "checkbox" in the "#admin-globalroles" "css_element"
    And I press "Save changes"
    And I log out

  Scenario Outline: Add role condition and verify it with a user with a role which can see the activity
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "<role_name>"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    When I log out
    And I log in as "<viewer>"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"

    Examples:
      | role_name     | viewer         |
      | Course Tester | courseroleuser |
      | Cat Tester    | catroleuser    |
      | Global Tester | globalroleuser |

  Scenario Outline: Add role condition and verify it with a user without a role which can see the activity (and which will also not see the message)
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "<role_name>"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    When I log out
    And I log in as "noroleuser"
    And I am on "Course 1" course homepage
    Then I should not see "P1" in the "region-main" "region"

    Examples:
      | role_name     |
      | Course Tester |
      | Cat Tester    |
      | Global Tester |

  Scenario Outline: Add role condition and verify it with a user without a role which can see the activity (but which will see the message)
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "<role_name>"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    When I log out
    And I log in as "noroleuser"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"
    And I should see "Not available unless: You are a(n) <role_name>" in the "region-main" "region"

    Examples:
      | role_name     |
      | Course Tester |
      | Cat Tester    |
      | Global Tester |

  Scenario Outline: Add role condition and verify it with a manager who can see the activity despite not having the role
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "<role_name>"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    When I log out
    And I log in as "manager1"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"
    And I should see "Not available unless: You are a(n) <role_name>" in the "region-main" "region"

    Examples:
      | role_name     |
      | Course Tester |
      | Cat Tester    |
      | Global Tester |

  Scenario: Add role condition for the guest role to a page activity and try to view it with a fully enrolled and a guest-enrolled student
    Given the following config values are set as admin:
      | config           | value | plugin            |
      | supportguestrole | YES   | availability_role |
    And I log in as "teacher1"
    And I am on the "Course 1" "enrolment methods" page
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes |
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "Guest"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "P1" in the "region-main" "region"
    # student2 is not enrolled in the course and therefore accesses it as a guest, which means the guest role condition applies to them.
    When I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"

  Scenario: Add role condition for the non-logged-in role to a page activity and try to view it with a logged-in student and a not-logged-in user
    Given the following "roles" exist:
      | name    | shortname | description | archetype |
      | Visitor | visitor   | Visitor     | guest     |
    And the following config values are set as admin:
      | config                 | value | plugin            |
      | supportnotloggedinrole | YES   | availability_role |
    And the following config values are set as admin:
      | config                         | value |
      | guestloginbutton               | 1     |
    And I log in as "admin"
    And I navigate to "Users > Permissions > User policies" in site administration
    And I set the field "Role for visitors" to "Visitor (visitor)"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on the "Course 1" "enrolment methods" page
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes |
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "Visitor"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "P1" in the "region-main" "region"
    When I log out
    And I log in as "guest"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"

  Scenario: Limit the roles which can be used in the condition to the teacher role
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    Then I should see "Teacher" in the ".availability_role select" "css_element"
    And I should see "Student" in the ".availability_role select" "css_element"
    And I should see "Manager" in the ".availability_role select" "css_element"
    And I should see "Non-editing teacher" in the ".availability_role select" "css_element"
    And I navigate to "Plugins > Availability restrictions > Restriction by role" in site administration
    And I click on "Manager" "checkbox" in the "#admin-courseroles" "css_element"
    And I click on "Non-editing teacher" "checkbox" in the "#admin-courseroles" "css_element"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    Then I should see "Teacher" in the ".availability_role select" "css_element"
    And I should see "Student" in the ".availability_role select" "css_element"
    And I should not see "Manager" in the ".availability_role select" "css_element"
    And I should not see "Non-editing teacher" in the ".availability_role select" "css_element"

  Scenario: Teacher without addinstance capability cannot add the role availability condition
    Given the following "permission overrides" exist:
      | capability                    | permission | role           | contextlevel | reference |
      | availability/role:addinstance | Prohibit   | editingteacher | Course       | C1        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then I should not see "Role" in the ".availability-buttons" "css_element"

  Scenario: Add negated role condition and view the negated availability message as teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "Teacher"
    # Set the restriction type to "must not" to negate the condition (restrict to users who are NOT a teacher).
    And I set the field "Restriction type" to "must not"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    # The teacher does not satisfy the "NOT teacher" condition, so the message is displayed.
    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"
    And I should see "Not available unless: You are not a(n) Teacher" in the "region-main" "region"
    # The student satisfies the "NOT teacher" condition, so no message is displayed.
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"
    And I should not see "Not available unless" in the "region-main" "region"

  Scenario: Nonsensical warning is shown when a role without module view capability is selected and persists after re-editing
    # Course Tester is a custom role with no capabilities at all, so restricting a page activity
    # to it is nonsensical: users with only that role can never view the activity.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    # Before choosing a specific role, the warning div must not exist in the DOM at all.
    Then ".availability_role_nonsensical_warning" "css_element" should not exist
    # Select a role that has no mod/page:view capability – the warning must appear.
    When I set the field "Role" to "Course Tester"
    Then ".availability_role_nonsensical_warning" "css_element" should exist
    And I should see "Warning:" in the ".availability_role_nonsensical_warning" "css_element"
    # Save the activity with this nonsensical restriction.
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    # Re-open the activity settings and verify the warning is still shown because the
    # nonsensical role is pre-selected from the saved condition.
    When I open "P1" actions menu
    And I click on "Edit settings" "link" in the "P1" activity
    And I expand all fieldsets
    Then ".availability_role_nonsensical_warning" "css_element" should exist
    And I should see "Warning:" in the ".availability_role_nonsensical_warning" "css_element"

  Scenario: Nonsensical warning is shown when a role has an explicit CAP_PROHIBIT override for the module view capability
    # The Non-editing teacher role normally has mod/page:view access, but after an explicit
    # Prohibit override in this course it can no longer view pages – the warning must appear.
    Given the following "permission overrides" exist:
      | capability    | permission | role    | contextlevel | reference |
      | mod/page:view | Prohibit   | teacher | Course       | C1        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a page to section "1" using the activity chooser
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    # Before choosing a specific role, the warning div must not exist in the DOM at all.
    Then ".availability_role_nonsensical_warning" "css_element" should not exist
    # Select the Non-editing teacher role – the explicit Prohibit override means non-editing
    # teachers can never view this activity, so the warning must appear.
    When I set the field "Role" to "Non-editing teacher"
    Then ".availability_role_nonsensical_warning" "css_element" should exist
    And I should see "Warning:" in the ".availability_role_nonsensical_warning" "css_element"
    # Save the activity with this nonsensical restriction.
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    # Re-open the activity settings and verify the warning is still shown because the
    # nonsensical role is pre-selected from the saved condition.
    When I open "P1" actions menu
    And I click on "Edit settings" "link" in the "P1" activity
    And I expand all fieldsets
    Then ".availability_role_nonsensical_warning" "css_element" should exist
    And I should see "Warning:" in the ".availability_role_nonsensical_warning" "css_element"
