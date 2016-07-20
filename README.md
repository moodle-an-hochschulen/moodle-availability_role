moodle-availability_role
========================

Moodle availability plugin which lets users restrict resources, activities and sections based on roles


Requirements
------------

This plugin requires Moodle 3.1+


Changes
-------

* 2016-07-19 - Check compatibility for Moodle 3.1, no functionality change
* 2016-02-10 - Change plugin version and release scheme to the scheme promoted by moodle.org, no functionality change
* 2016-01-01 - Initial version


Installation
------------

Install the plugin like any other plugin to folder
/availability/condition/role

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage
-----

After installing, availability_role is ready to use without the need for any configuration.

Teachers (and other users with editing rights) can add the "Role" availability condition to activities / resources / sections in their courses. While adding the condition, they have to define the role which students have to have in course context to access the activity / resource / section.

If you want to learn more about using availability plugins in Moodle, please see https://docs.moodle.org/en/Restrict_access.


Themes
------

availability_role should work with all Bootstrap based Moodle themes.


Motivation for this plugin
--------------------------

If your teachers want to restrict activities / resources / sections in their course to a subset of the course participants and these course participants share a common course role, this plugin is for you.

Have a look at an example:
* Tim Teacher is an editing teacher in course A.
* Carl Clueless and Steve Smart are Tim's student assistants.
* As Moodle admin, you have already created a custom course role called "student assistant" in your Moodle installation. Carl and Steve have this role in course A to do their work.
* If Tim wants to provide activities / resources / sections only for Carl and Steve in course A, for example a forum activity where they can discuss internal stuff, he had to do some workarounds in the past. The most popular solution was to put Carl and Steve into a group and restrict the activities / resources / sections to this group, but there were even more complicated workarounds.

With availability_role, Tim does not need any workarounds anymore. He is just able to restrict his activities / resources / sections to a certain course role and all users who have this role in the course context have access.


Pitfalls
-------

In Moodle, roles normally do not control things directly. Instead, roles contain (multiple) capabilities and these capabilities control things.

There is the capability moodle/course:viewhiddenactivities (see https://docs.moodle.org/en/Capabilities/moodle/course:viewhiddenactivities) which is contained in the manager, teacher and non-editing teacher roles by default. If a user has a role which contains moodle/course:viewhiddenactivities, he is able to use an activity / resource / section even if the teacher has restricted it with availability_role to some other role.

Because of that, availability_role can't be used to hide activities / resources / sections from users who already are allowed to view hidden activities in the course. Use this availability restriction plugin wisely and explain to your teachers what is possible and what is not.


Further information
-------------------

availability_role is found in the Moodle Plugins repository: http://moodle.org/plugins/view/availability_role

Report a bug or suggest an improvement: https://github.com/moodleuulm/moodle-availability_role/issues


Moodle release support
----------------------

Due to limited resources, availability_role is only maintained for the most recent major release of Moodle. However, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that availability_role still works with a new major relase - please let us know on https://github.com/moodleuulm/moodle-availability_role/issues


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send me a pull request on
github with modifications.


Copyright
---------

Bence Laky
Synergy Learning UK
www.synergy-learning.com

on behalf of

University of Ulm
kiz - Media Department
Team Web & Teaching Support
Alexander Bias
