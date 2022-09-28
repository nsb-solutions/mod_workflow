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
        if ($this->current && $this->current->course) {
            $course_id = $this->current->course;
        } else {
            $course_id = required_param('course', PARAM_ALPHANUM);
        }

        $coursemodule_id = $this->optional_param('update', 0, PARAM_ALPHANUM);

        // Load previous form if exists
        if ($coursemodule_id) {
            $prev_form = $this->get_prev_form($DB, $coursemodule_id);
        }

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

        $workflow_type_select = $mform->addElement('select', 'workflow_type_select', get_string('workflowtype', 'workflow'), $workflow_types);
        $mform->addHelpButton('workflow_type_select', 'workflowtype', 'workflow');
        if (isset($prev_form)) {
            $workflow_type_select->setSelected($prev_form->type);
        }

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Select instructor
        $lecturer_select = $mform->addElement('select', 'lecturer_select', 'Select lecturer', $lecturers);
        $mform->addHelpButton('lecturer_select', 'workflowtype', 'workflow');
        if (isset($prev_form)) {
            $lecturer_select->setSelected($prev_form->lecturer);
        }

        // Select instructor
        $instructor_select = $mform->addElement('select', 'instructor_select', 'Select instructor', $instructors);
        $mform->addHelpButton('instructor_select', 'workflowtype', 'workflow');
        if (isset($prev_form)) {
            $instructor_select->setSelected($prev_form->instructor);
        }

        // Select assignments
        $assignment_select = $mform->addElement('select', 'assignment_select', 'Select assignment', $assignments);
        $mform->addHelpButton('assignment_select', 'workflowtype', 'workflow');
        if (isset($prev_form) && $prev_form->type==='assignment') {
            $assignment_select->setSelected('assignment');
        }

        // Select quiz
        $quiz_select = $mform->addElement('select', 'quiz_select', 'Select quiz', $quizzes);
        $mform->addHelpButton('quiz_select', 'workflowtype', 'workflow');
        if (isset($prev_form) && $prev_form->type==='quiz') {
            $quiz_select->setSelected('quiz');
        }


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
        $quizznames_db = $DB->get_records_sql("SELECT mdl_quiz.id, mdl_quiz.name
                                            FROM mdl_course_modules
                                            INNER JOIN mdl_modules
                                            ON mdl_course_modules.module = mdl_modules.id
                                            INNER JOIN mdl_quiz
                                            ON mdl_course_modules.instance = mdl_quiz.id
                                            WHERE mdl_modules.name='quiz' AND mdl_course_modules.deletioninprogress=0 AND mdl_quiz.course=?;
                                 ", [$course_id]);
        $quizzes = array();
        foreach ($quizznames_db as $key => $value) {
            $quizzes[$value->id]=$value->name;
        }

        return $quizzes;
    }

    private function get_assignments($DB, $course_id) {
        $assignmentnames_db = $DB->get_records_sql("SELECT mdl_assign.id, mdl_assign.name
                                                FROM mdl_course_modules
                                                INNER JOIN mdl_modules
                                                ON mdl_course_modules.module = mdl_modules.id
                                                INNER JOIN mdl_assign
                                                ON mdl_course_modules.instance = mdl_assign.id
                                                WHERE mdl_modules.name='assign' AND mdl_course_modules.deletioninprogress=0 AND mdl_assign.course=?;
                                ", [$course_id]);
        $assignments = array();
        foreach ($assignmentnames_db as $key => $value) {
            $assignments[$value->id]=$value->name;
        }

        return $assignments;
    }

    private function get_prev_form($DB, $coursemodule_id) {
        $prevform_db = $DB->get_record_sql("SELECT *
                                        FROM mdl_workflow
                                        WHERE id IN (
                                        SELECT instance
                                        FROM mdl_course_modules
                                        WHERE id=? );
                            ", [$coursemodule_id]);

        return $prevform_db;
    }
}
