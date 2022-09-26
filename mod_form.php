<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main workflow configuration form.
 *
 * @package     workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
/**
 * Module instance settings form.
 *
 * @package     workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_workflow_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        global $PAGE;
        global $DB;
        global $USER;

        // Load javascript
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/workflow/module.js'));

        // Get course id from url
        $course_id = required_param('course', PARAM_ALPHANUM);

        // Load lecturers from database
        $lecturers = $this->get_lecturers($DB, $course_id);

        // Load instructors from database
        $instructors = $this->get_instructors($DB, $course_id);

        // Load assignments from databasw
        $assignments = $this->get_assignments($DB, $course_id);

        // Load quizzes from database
        $quizzes = $this->get_quizzes($DB, $course_id);

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('workflowname', 'workflow'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'workflowname', 'workflow');

        $workflow_types = array ('assignment'  => get_string('assignment', 'workflow'),
            'quiz' => get_string('quiz', 'workflow'),
            'other'   => get_string('other', 'workflow'));

        $mform->addElement('select', 'workflow_type_select', get_string('workflowtype', 'workflow'), $workflow_types);
        $mform->addHelpButton('workflow_type_select', 'workflowtype', 'workflow');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Select instructor
        $mform->addElement('select', 'lecturer_select', 'Select lecturer', $lecturers);
        $mform->addHelpButton('lecturer_select', 'workflowtype', 'workflow');

        // Select instructor
        $mform->addElement('select', 'instructor_select', 'Select instructor', $instructors);
        $mform->addHelpButton('instructor_select', 'workflowtype', 'workflow');

        // Select assignments
        $mform->addElement('select', 'assignment_select', 'Select assignment', $assignments);
        $mform->addHelpButton('assignment_select', 'workflowtype', 'workflow');

        // Select quiz
        $mform->addElement('select', 'quiz_select', 'Select quiz', $quizzes);
        $mform->addHelpButton('quiz_select', 'workflowtype', 'workflow');


        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'workflowname', 'workflow');

        $mform->addElement('header', 'availability', get_string('availability', 'assign'));
        $mform->setExpanded('availability', true);

        $name = get_string('allowsubmissionsfromdate', 'assign');
        $options = array('optional'=>true);
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', $name, $options);
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'assign');

        $name = get_string('duedate', 'assign');
        $mform->addElement('date_time_selector', 'duedate', $name, array('optional'=>true));
        $mform->addHelpButton('duedate', 'duedate', 'assign');

        $name = get_string('cutoffdate', 'assign');
        $mform->addElement('date_time_selector', 'cutoffdate', $name, array('optional'=>true));
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'assign');

        $name = get_string('alwaysshowdescription', 'assign');
        $mform->addElement('checkbox', 'alwaysshowdescription', $name);
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'assign');
        $mform->disabledIf('alwaysshowdescription', 'allowsubmissionsfromdate[enabled]', 'notchecked');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    private function get_instructors($DB, $course_id) {
         $instructors_db = $DB->get_records_sql("SELECT u.id, u.email
                                    FROM mdl_course c
                                    JOIN mdl_context ct ON c.id = ct.instanceid
                                    JOIN mdl_role_assignments ra ON ra.contextid = ct.id
                                    JOIN mdl_user u ON u.id = ra.userid
                                    JOIN mdl_role r ON r.id = ra.roleid
                                    WHERE (r.id = 3 OR r.id = 4) AND c.id = ?;", [$course_id]);

        $instructors = array();
        foreach ($instructors_db as $key => $value) {
            $instructors[$value->id]=$value->email;
        }

        return $instructors;
    }

    private function get_lecturers($DB, $course_id) {
        $lecturers_db = $DB->get_records_sql("SELECT u.id, u.email
                                    FROM mdl_course c
                                    JOIN mdl_context ct ON c.id = ct.instanceid
                                    JOIN mdl_role_assignments ra ON ra.contextid = ct.id
                                    JOIN mdl_user u ON u.id = ra.userid
                                    JOIN mdl_role r ON r.id = ra.roleid
                                    WHERE r.id = 3 AND c.id = ?;", [$course_id]);

        $lecturers = array();
        foreach ($lecturers_db as $key => $value) {
            $lecturers[$value->id]=$value->email;
        }

        return $lecturers;
    }

    private function get_quizzes($DB, $course_id) {
        $quizznames_db = $DB->get_records_sql("SELECT id, name
                                FROM mdl_quiz
                                WHERE course=?;
                                ", [$course_id]);
        $quizzes = array();
        foreach ($quizznames_db as $key => $value) {
            $quizzes[$value->id]=$value->name;
        }

        return $quizzes;
    }

    private function get_assignments($DB, $course_id) {
        $assignmentnames_db = $DB->get_records_sql("SELECT id, name
                                FROM mdl_assign
                                WHERE course=?;
                                ", [$course_id]);
        $assignments = array();
        foreach ($assignmentnames_db as $key => $value) {
            $assignments[$value->id]=$value->name;
        }

        return $assignments;
    }
}
