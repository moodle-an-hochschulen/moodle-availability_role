@availability @availability_role @javascript
Feature: availability_role
  In order to control student access to activities
  As a teacher
  I need to set role conditions which prevent student access

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
      | student2 |
      | manager1 |
    And the following "system role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Add role condition for the teacher role to a page activity and try to view it as teacher (who will see it)
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "Teacher"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"

  Scenario: Add role condition for the teacher role to a page activity and try to view it as student (who will not see it)
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "Teacher"
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

  Scenario: Add role condition for the teacher role to a page activity and try to view it as manager (who will see it although he isn't a teacher)
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Role" "button"
    And I set the field "Role" to "Teacher"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I click on "Save and return to course" "button"
    When I log out
    And I log in as "manager1"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"

  Scenario: Add role condition for the guest role to a page activity and try to view it with a fully enrolled and a guest-enrolled student
    Given the following config values are set as admin:
      | config                   | value | plugin            |
      | setting_supportguestrole | YES   | availability_role |
    And I log in as "teacher1"
    And I am on the "Course 1" "enrolment methods" page
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes |
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1"
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
    When I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"
