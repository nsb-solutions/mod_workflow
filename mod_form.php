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

        // Load javascript
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/workflow/module.js'));

        // Load instructors from database
        $instructors_db = $DB->get_records_sql("SELECT email
                                FROM mdl_user
                                WHERE id in (
                                SELECT userid
                                FROM mdl_role_assignments
                                WHERE roleid=3 or roleid=4
                            );");

        $instructor_keys = array_keys($instructors_db);

        $course_id = required_param('course', PARAM_ALPHANUM);

        // Load assignments from databasw
        $assignmentnames_db = $DB->get_records_sql("SELECT name
                                FROM mdl_assign
                                WHERE course=?;
                                ", [$course_id]);
        $assignmentnames_keys = array_keys($assignmentnames_db);

        // Load quizzes from database
        $quizznames_db = $DB->get_records_sql("SELECT name
                                FROM mdl_quiz
                                WHERE course=?;
                                ", [$course_id]);
        $quizznames_keys = array_keys($quizznames_db);

        $mform = $this->_form;

        $workflow_type = $this->optional_param('workflow_type', 'assignment', PARAM_ALPHA);

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
            'exam'   => get_string('exam', 'workflow'));

        $workflow_type_select = $mform->addElement('select', 'workflow_type_select', get_string('workflowtype', 'workflow'), $workflow_types);
        $mform->addHelpButton('workflow_type_select', 'workflowtype', 'workflow');
        $workflow_type_select->setSelected($workflow_type);

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Select instructor
        $mform->addElement('select', 'instructor_select', 'Select instructor', array_combine($instructor_keys, $instructor_keys));
        $mform->addHelpButton('workflow_type_select', 'workflowtype', 'workflow');

        // Select reference
        $mform->addElement('select', 'assignment_select', 'Select assignment', array_combine($assignmentnames_keys, $assignmentnames_keys));
        $mform->addHelpButton('workflow_type_select', 'workflowtype', 'workflow');
        $mform->addElement('select', 'quiz_select', 'Select quiz', array_combine($quizznames_keys, $quizznames_keys));
        $mform->addHelpButton('workflow_type_select', 'workflowtype', 'workflow');


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

        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'assign'));

        $name = get_string('submissiondrafts', 'assign');
        $mform->addElement('selectyesno', 'submissiondrafts', $name);
        $mform->addHelpButton('submissiondrafts', 'submissiondrafts', 'assign');

        $name = get_string('requiresubmissionstatement', 'assign');
        $mform->addElement('selectyesno', 'requiresubmissionstatement', $name);
        $mform->addHelpButton('requiresubmissionstatement',
            'requiresubmissionstatement',
            'assign');
        $mform->setType('requiresubmissionstatement', PARAM_BOOL);

        $options = array(
            ASSIGN_ATTEMPT_REOPEN_METHOD_NONE => get_string('attemptreopenmethod_none', 'mod_assign'),
            ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL => get_string('attemptreopenmethod_manual', 'mod_assign'),
            ASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS => get_string('attemptreopenmethod_untilpass', 'mod_assign')
        );
        $mform->addElement('select', 'attemptreopenmethod', get_string('attemptreopenmethod', 'mod_assign'), $options);
        $mform->addHelpButton('attemptreopenmethod', 'attemptreopenmethod', 'mod_assign');

        $options = array(ASSIGN_UNLIMITED_ATTEMPTS => get_string('unlimitedattempts', 'mod_assign'));
        $options += array_combine(range(1, 30), range(1, 30));
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'mod_assign'), $options);
        $mform->addHelpButton('maxattempts', 'maxattempts', 'assign');
        $mform->hideIf('maxattempts', 'attemptreopenmethod', 'eq', ASSIGN_ATTEMPT_REOPEN_METHOD_NONE);


        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
