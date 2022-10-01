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

require_once($CFG->dirroot . '/mod/workflow/mod_form.php');
require_once($CFG->dirroot . '/mod/workflow/renderable.php');

/**
 * Module instance settings form.
 *
 * @package     workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class workflow {
    /** @var stdClass the workflow record that contains the global settings for this workflow instance */
    private $instance;

    /** @var array $var array an array containing requests of this workflow */
    private $userinstances = [];

    /** @var context the context of the course module for this workflow instance
     *               (or just the course if we are creating a new one)
     */
    private $context;

    /** @var stdClass the course this workflow instance belongs to */
    private $course;

    /** @var assign_renderer the custom renderer for this module */
    private $output;

    /** @var cm_info the course module for this workflow instance */
    private $coursemodule;

    /** @var array cache for things like the coursemodule name or the scale menu -
     *             only lives for a single request.
     */
    private $cache;

    /** @var array Array of error messages encountered during the execution of workflow related operations. */
    private $errors = array();

    /**
     * Constructor for the base workflow class.
     *
     * Note: For $coursemodule you can supply a stdclass if you like, but it
     * will be more efficient to supply a cm_info object.
     *
     * @param mixed $coursemodulecontext context|null the course module context
     *                                   (or the course context if the coursemodule has not been
     *                                   created yet).
     * @param mixed $coursemodule the current course module if it was already loaded,
     *                            otherwise this class will load one from the context as required.
     * @param mixed $course the current course  if it was already loaded,
     *                      otherwise this class will load one from the context as required.
     */

    public function __construct($coursemodulecontext, $coursemodule, $course) {
        $this->context = $coursemodulecontext;
        $this->course = $course;

        // Ensure that $this->coursemodule is a cm_info object (or null).
        $this->coursemodule = cm_info::create($coursemodule);

        // Temporary cache only lives for a single request - used to reduce db lookups.
        $this->cache = array();
    }

    /**
     * Add this instance to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @param bool $callplugins This is used to skip the plugin code
     *             when upgrading an old assignment to a new one (the plugins get called manually)
     * @return mixed false if an error occurs or the int id of the new instance
     */
    public function add_instance(stdClass $formdata) {
        global $DB;

        // Add the database record.
        $update = new stdClass();
        $update->name = $formdata->name;
        $update->timemodified = time();
        $update->timecreated = time();
        $update->course = $formdata->course;
        $update->intro = $formdata->intro;
        $update->introformat = $formdata->introformat;
        $update->type = $formdata->workflow_type_select;
        $update->lecturer = $formdata->lecturer_select;
        $update->instructor = $formdata->instructor_select;
        $update->allowsubmissionsfromdate = $formdata->allowsubmissionsfromdate;
        $update->duedate = $formdata->duedate;
        $update->cutoffdate = $formdata->cutoffdate;

        $returnid = $DB->insert_record('workflow', $update);

        if ($update->type==='assignment') {
            $update_assignment = new stdClass();
            $update_assignment->workflow = $returnid;
            $update_assignment->assignment = $formdata->assignment_select;
            $returnid_assignment = $DB->insert_record('workflow_assignment', $update_assignment);
        } else if ($update->type==='quiz') {
            $update_quiz = new stdClass();
            $update_quiz->workflow = $returnid;
            $update_quiz->quiz = $formdata->quiz_select;
            $returnid_quiz = $DB->insert_record('workflow_quiz', $update_quiz);
        } else if ($update->type==='other') {
            $update_other = new stdClass();
            $update_other->workflow = $returnid;
            $returnid_other = $DB->insert_record('workflow_other', $update_other);
        }

        return $returnid;
    }

    /**
     * Delete this instance from the database.
     *
     * @return bool false if an error occurs
     */
    public function delete_instance($id) {
        global $DB;
        $result = true;

        $workflow_record = $DB->get_record('workflow', array('id'=>$id), 'type');

        if ($workflow_record->type==='assignment') {
            $DB->delete_records('workflow_assignment', array('workflow'=>$id));
        } else if ($workflow_record->type==='quiz') {
            $DB->delete_records('workflow_quiz', array('workflow'=>$id));
        } else if ($workflow_record->type==='other') {
            $DB->delete_records('workflow_other', array('workflow'=>$id));
        }

        // Delete the instance.
        $DB->delete_records('workflow', array('id'=>$id));

        return $result;
    }

    /**
     * Update this instance in the database.
     *
     * @param stdClass $formdata - the data submitted from the form
     * @return bool false if an error occurs
     */
    public function update_instance(stdClass $formdata) {
        global $DB;

        // Add the database record.
        $update = new stdClass();
        $update->id = $formdata->instance;
        $update->name = $formdata->name;
        $update->timemodified = time();
        $update->course = $formdata->course;
        $update->intro = $formdata->intro;
        $update->introformat = $formdata->introformat;
        $update->type = $formdata->workflow_type_select;
        $update->lecturer = $formdata->lecturer_select;
        $update->instructor = $formdata->instructor_select;
        $update->allowsubmissionsfromdate = $formdata->allowsubmissionsfromdate;
        $update->duedate = $formdata->duedate;
        $update->cutoffdate = $formdata->cutoffdate;

        $result = $DB->update_record('workflow', $update);

        if ($update->type==='assignment') {
            $update_assignment = $DB->get_record('workflow_assignment', array('workflow'=>$formdata->instance), '*');
            $update_assignment->assignment = $formdata->assignment_select;
            $result_assignment = $DB->update_record('workflow_assignment', $update_assignment);
        } else if ($update->type==='quiz') {
            $update_quiz = $DB->get_record('workflow_quiz', array('workflow'=>$formdata->instance), '*');
            $update_quiz->quiz = $formdata->quiz_select;
            $result_quiz = $DB->update_record('workflow_quiz', $update_quiz);
        } else if ($update->type==='other') {
            $update_other = $DB->get_record('workflow_other', array('workflow'=>$formdata->instance), '*');
            $result_other = $DB->update_record('workflow_other', $update_other);
        }

        return $result;
    }

    /**
     * Get the context of the current course.
     *
     * @return mixed context|null The course context
     */
    public function get_course_context() {
        if (!$this->context && !$this->course) {
            throw new coding_exception('Improper use of the assignment class. ' .
                'Cannot load the course context.');
        }
        if ($this->context) {
            return $this->context->get_course_context();
        } else {
            return context_course::instance($this->course->id);
        }
    }

    /**
     * Get the current course.
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;

        if ($this->course && is_object($this->course)) {
            return $this->course;
        }

        if (!$this->context) {
            return null;
        }
        $params = array('id' => $this->get_course_context()->instanceid);
        $this->course = $DB->get_record('workflow', $params, '*', MUST_EXIST);

        return $this->course;
    }

    /**
     * Get the current course module.
     *
     * @return cm_info|null The course module or null if not known
     */
    public function get_course_module() {
        if ($this->coursemodule) {
            return $this->coursemodule;
        }
        if (!$this->context) {
            return null;
        }

        if ($this->context->contextlevel == CONTEXT_MODULE) {
            $modinfo = get_fast_modinfo($this->get_course());
            $this->coursemodule = $modinfo->get_cm($this->context->instanceid);
            return $this->coursemodule;
        }
        return null;
    }

    /**
     * Get the settings for the current instance of this workflow.
     *
     * @return stdClass The settings
     * @throws dml_exception
     */
    public function get_default_instance() {
        global $DB;
        if (!$this->instance && $this->get_course_module()) {
            $params = array('id' => $this->get_course_module()->instance);
            $this->instance = $DB->get_record('workflow', $params, '*', MUST_EXIST);

            $this->userinstances = [];
        }
        return $this->instance;
    }

    /**
     * Get the settings for the current instance of this assignment
     * @param int|null $userid the id of the user to load the assign instance for.
     * @return stdClass The settings
     */
    public function get_instance(int $userid = null) : stdClass {
        global $USER;
        $userid = $userid ?? $USER->id;

        $this->instance = $this->get_default_instance();

        // If we have the user instance already, just return it.
        if (isset($this->userinstances[$userid])) {
            return $this->userinstances[$userid];
        }

        // Calculate properties which vary per user.
        $this->userinstances[$userid] = $this->calculate_properties($this->instance, $userid);
        return $this->userinstances[$userid];
    }

    /**
     * Calculates and updates various properties based on the specified user.
     *
     * @param stdClass $record the raw assign record.
     * @param int $userid the id of the user to calculate the properties for.
     * @return stdClass a new record having calculated properties.
     */
    private function calculate_properties(\stdClass $record, int $userid) : \stdClass {
        $record = clone ($record);

        // Relative dates.
        if (!empty($record->duedate)) {
            $course = $this->get_course();
            $usercoursedates = course_get_course_dates_for_user_id($course, $userid);
            if ($usercoursedates['start']) {
                $userprops = ['duedate' => $record->duedate + $usercoursedates['startoffset']];
                $record = (object) array_merge((array) $record, (array) $userprops);
            }
        }
        return $record;
    }

    /**
     * Get context module.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Update the module completion status (set it viewed) and trigger module viewed event.
     *
     * @since Moodle 3.2
     */
    public function set_module_viewed() {
        $completion = new completion_info($this->get_course());
        $completion->set_module_viewed($this->get_course_module());

        // Trigger the course module viewed event.
        $workflowinstance = $this->get_instance();
        $params = [
            'objectid' => $workflowinstance->id,
            'context' => $this->get_context()
        ];

        $event = \mod_workflow\event\course_module_viewed::create($params);

        $event->add_record_snapshot('workflow', $workflowinstance);
        $event->trigger();
    }

    /**
     * Lazy load the page renderer and expose the renderer to plugins.
     *
     * @return assign_renderer
     */
    public function get_renderer() {
        global $PAGE;
        if ($this->output) {
            return $this->output;
        }
        $this->output = $PAGE->get_renderer('mod_workflow', null, RENDERER_TARGET_GENERAL);
        return $this->output;
    }


    /**
     * Display the page footer.
     *
     * @return string
     */
    protected function view_footer() {
        // When viewing the footer during PHPUNIT tests a set_state error is thrown.
        if (!PHPUNIT_TEST) {
            return $this->get_renderer()->render_footer();
        }

        return '';
    }

    /**
     * Display the workflow, used by view.php
     *
     * The workflow is displayed differently depending on your role,
     * the settings for the workflow and the status of the workflow.
     *
     * @param string $action The current action if any.
     * @param array $args Optional arguments to pass to the view (instead of getting them from GET and POST).
     * @return string - The page output.
     */
    public function view($action='', $args = array()) {
        global $PAGE;

        $o = '';
        $mform = null;
        $nextpageparams = array();

        if (!empty($this->get_course_module()->id)) {
            $nextpageparams['id'] = $this->get_course_module()->id;
        }

        // Handle form submissions first.
        if ($action == 'TODO') {

        }
        else {
            $o .= $this->view_submission_page();
        }

        return $o;
    }

    /**
     * View submissions page (contains details of current submission).
     *
     * @return string
     */
    protected function view_submission_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();

//        $this->add_grade_notices();

        $o = '';

        $postfix = '';
//        if ($this->has_visible_attachments()) {
//            $postfix = $this->render_area_files('mod_assign', ASSIGN_INTROATTACHMENT_FILEAREA, 0);
//        }

        $o .= $this->get_renderer()->render(new workflow_header($instance,
            $this->get_context(),
            true,
            $this->get_course_module()->id,
            '', '', $postfix));

//        if ($this->can_view_grades()) {
//            // Group selector will only be displayed if necessary.
//            $currenturl = new moodle_url('/mod/assign/view.php', array('id' => $this->get_course_module()->id));
//            $o .= groups_print_activity_menu($this->get_course_module(), $currenturl->out(), true);
//
//            $summary = $this->get_assign_grading_summary_renderable();
//            $o .= $this->get_renderer()->render($summary);
//        }
//        $grade = $this->get_user_grade($USER->id, false);
//        $submission = $this->get_user_submission($USER->id, false);

//        if ($this->can_view_submission($USER->id)) {
//            $o .= $this->view_student_summary($USER, true);
//        }

        $coursemodule_id = required_param('id', PARAM_ALPHANUM);
        $workflow = $this->get_workflow($DB, $coursemodule_id);
        $lecturer = $this->get_useremail($DB, $workflow->lecturer);
        $instructor = $this->get_useremail($DB, $workflow->instructor);
        $item = '';
        if ($workflow->type==='assignment') {
            $item = $this->get_assignmentname_form_workflow($DB, $workflow->id)->name;
        } else if ($workflow->type==='quiz') {
            $item = $this->get_quizname_form_workflow($DB, $workflow->id)->name;
        }
        $participants = 0;
        $requested = 0;
        $pending = 0;

        $summary = new workflow_grading_summary(
            $participants,
            $workflow->type,
            $item,
            true,
            $requested,
            $workflow->cutoffdate,
            $workflow->duedate,
            $coursemodule_id,
            $pending,
            $lecturer->email,
            $instructor->email,
            '0',
            "1663711200",
            true,
            '1'
        );

        $o .= $this->get_renderer()->render($summary);

        $o .= $this->view_footer();

//        \mod_assign\event\submission_status_viewed::create_from_assign($this)->trigger();

        return $o;
    }

    public function get_useremail($DB, $user_id) {
        $user_db = $DB->get_record_sql("SELECT email
                                    FROM mdl_user
                                    WHERE id = ?;", [$user_id]);

        return $user_db;
    }

    public function get_quizname_form_workflow($DB, $workflow_id) {
        $quizid = $DB->get_record_sql("SELECT name
                                    FROM mdl_quiz
                                    WHERE id IN (
                                        SELECT quiz
                                        FROM mdl_workflow_quiz
                                        WHERE workflow = ?
                                    );
                            ", [$workflow_id]);

        return $quizid;
    }

     function get_assignmentname_form_workflow($DB, $workflow_id) {
        $assignmentid = $DB->get_record_sql("SELECT name
                                            FROM mdl_assign
                                            WHERE id IN (
                                                SELECT assignment
                                                FROM mdl_workflow_assignment
                                                WHERE workflow = ?
                                            );
                            ", [$workflow_id]);

        return $assignmentid;
    }

    private function get_workflow($DB, $coursemodule_id) {
        $workflow = $DB->get_record_sql("SELECT *
                                        FROM mdl_workflow
                                        WHERE id IN (
                                        SELECT instance
                                        FROM mdl_course_modules
                                        WHERE id=? );
                            ", [$coursemodule_id]);

        return $workflow;
    }
}