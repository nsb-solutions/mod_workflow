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
 * Unit tests for (some of) mod/workflow/lib.php.
 *
 * @package    mod_workflow
 * @category   phpunit
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

use \core_calendar\local\api as calendar_local_api;
use \core_calendar\local\event\container as calendar_event_container;

/**
 * Unit tests for (some of) mod/workflow/lib.php.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_workflow_testcase extends advanced_testcase {
    /**
     * test test
     */
    public function test_workflow_create() {
        $this->resetAfterTest();
        $this->setUser(2);

        global $CFG;
        require_once($CFG->dirroot . '/mod/workflow/locallib.php');

        $workflow = new workflow(1, null, null);
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $instructor = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $params = [];
        $params['course'] = $course->id;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $instance = $generator->create_instance($params, []);

        $formdata = new stdClass();
        $formdata->name = 'Project Proposal Submission Extend';
        $formdata->course = $course->id;
        $formdata->intro = '';
        $formdata->introformat = '1';
        $formdata->workflow_type_select = 'assignment';
        $formdata->lecturer_select = $teacher->id;
        $formdata->instructor_select = $instructor->id;
        $formdata->assignment_select = $instance->id;
        $formdata->allowsubmissionsfromdate = 0;
        $formdata->duedate = 0;
        $formdata->cutoffdate = 0;

        $returnid = $workflow->add_instance($formdata);

        $this->assertIsInt($returnid);
    }

    public function test_workflow_delete() {
        $this->resetAfterTest();
        $this->setUser(2);

        global $CFG;
        require_once($CFG->dirroot . '/mod/workflow/locallib.php');

        $workflow = new workflow(1, null, null);
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $instructor = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $params['course'] = $course->id;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $instance = $generator->create_instance($params, []);

        $formdata = new stdClass();
        $formdata->name = 'Project Proposal Submission Extend';
        $formdata->course = $course->id;
        $formdata->intro = '';
        $formdata->introformat = '1';
        $formdata->workflow_type_select = 'assignment';
        $formdata->lecturer_select = $teacher->id;
        $formdata->instructor_select = $instructor->id;
        $formdata->assignment_select = $instance->id;
        $formdata->allowsubmissionsfromdate = 0;
        $formdata->duedate = 0;
        $formdata->cutoffdate = 0;

        $returnid = $workflow->add_instance($formdata);

        $result = $workflow->delete_instance($returnid);
        $this->assertTrue($result);
    }

    public function test_workflow_edit()
    {
        $this->resetAfterTest();
        $this->setUser(2);

        global $CFG;
        require_once($CFG->dirroot . '/mod/workflow/locallib.php');

        $workflow = new workflow(1, null, null);
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $instructor = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $params['course'] = $course->id;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $instance = $generator->create_instance($params, []);

        $formdata = new stdClass();
        $formdata->name = 'Project Proposal Submission Extend';
        $formdata->course = $course->id;
        $formdata->intro = '';
        $formdata->introformat = '1';
        $formdata->workflow_type_select = 'assignment';
        $formdata->lecturer_select = $teacher->id;
        $formdata->instructor_select = $instructor->id;
        $formdata->assignment_select = $instance->id;
        $formdata->allowsubmissionsfromdate = 0;
        $formdata->duedate = 0;
        $formdata->cutoffdate = 0;

        $returnid = $workflow->add_instance($formdata);

        $formdata->instance = $returnid;
        $formdata->name = 'Edited Project Proposal Submission Extend';

        $newid = $workflow->update_instance($formdata);
        $this->assertTrue($newid);
    }
}