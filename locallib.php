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

    /** @var bool whether to exclude users with inactive enrolment */
    private $showonlyactiveenrol = null;

    /** @var array cached list of participants for this workflow. The cache key will be group, showactive and the context id */
    private $participants = array();

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
     * Get error messages.
     *
     * @return array The array of error messages
     */
    protected function get_error_messages(): array {
        return $this->errors;
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
     * Check is only active users in course should be shown.
     *
     * @return bool true if only active users should be shown.
     */
    public function show_only_active_users() {
        global $CFG;

        if (is_null($this->showonlyactiveenrol)) {
            $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol);
            $this->showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol);

            if (!is_null($this->context)) {
                $this->showonlyactiveenrol = $this->showonlyactiveenrol ||
                    !has_capability('moodle/course:viewsuspendedusers', $this->context);
            }
        }
        return $this->showonlyactiveenrol;
    }

    /**
     * Load a list of users enrolled in the current course with the specified permission and group.
     * 0 for no group.
     * Apply any current sort filters from the grading table.
     *
     * @param int $currentgroup
     * @param bool $idsonly
     * @param bool $tablesort
     * @return array List of user records
     */
    public function list_participants($currentgroup, $idsonly) {
        global $DB, $USER;

        // Get the last known sort order for the grading table.

        if (empty($currentgroup)) {
            $currentgroup = 0;
        }

        $key = $this->context->id . '-' . $currentgroup . '-' . $this->show_only_active_users();
        if (!isset($this->participants[$key])) {
            list($esql, $params) = get_enrolled_sql($this->context, 'mod/assign:submit', $currentgroup,
                $this->show_only_active_users());

            $fields = 'u.*';
            $orderby = 'u.lastname, u.firstname, u.id';

            $sql = "SELECT $fields
                      FROM {user} u
                      JOIN ($esql) je ON je.id = u.id
                     WHERE u.deleted = 0
                  ORDER BY $orderby";

            $users = $DB->get_records_sql($sql, $params);

            $cm = $this->get_course_module();
            $info = new \core_availability\info_module($cm);
            $users = $info->filter_user_list($users);

            $this->participants[$key] = $users;
        }

        if ($idsonly) {
            $idslist = array();
            foreach ($this->participants[$key] as $id => $user) {
                $idslist[$id] = new stdClass();
                $idslist[$id]->id = $id;
            }
            return $idslist;
        }
        return $this->participants[$key];
    }

    /**
     * Load a count of active users enrolled in the current course with the specified permission and group.
     * 0 for no group.
     *
     * @param int $currentgroup
     * @return int number of matching users
     */
    public function count_participants($currentgroup) {
        return count($this->list_participants($currentgroup, true));
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
    public function view($action = '', $args = array())
    {
        global $PAGE, $USER;

        $o = '';
        $mform = null;
        $nextpageparams = array();

        // Append course id to parameters
        if (!empty($this->get_course_module()->id)) {
            $nextpageparams['id'] = $this->get_course_module()->id;
        }

        // Handle form submissions first.
        if($action == 'savesubmission') {
            $this->process_save_submission();
            $nextpageparams['action'] = '';
            $action = 'redirect';
        }

        elseif ($action == 'removesubmissionconfirm'){
            $this->process_remove_submission();
            $nextpageparams['action'] = '';
            $action = 'redirect';
        }

        elseif ($action == 'instructorapproved') {
            $this->process_instructor_approve();
            $nextpageparams['action'] = '';
            $action = 'redirect';
        }

        elseif ($action == 'requestrejected') {
            $this->process_request_reject();
            $nextpageparams['action'] = '';
            $action = 'redirect';
        }

        elseif ($action == 'lecturerapproved') {
            $this->process_lecturer_approve();
            $nextpageparams['action'] = '';
            $action = 'redirect';
        }

        // Handle redirects
        if ($action == 'redirect') {
            $nextpageurl = new moodle_url('/mod/workflow/view.php', $nextpageparams);
            $messages = '';
            $messagetype = \core\output\notification::NOTIFY_INFO;
            $errors = $this->get_error_messages();
            if (!empty($errors)) {
                $messages = html_writer::alist($errors, ['class' => 'mb-1 mt-1']);
                $messagetype = \core\output\notification::NOTIFY_ERROR;
            }
            redirect($nextpageurl, $messages, null, $messagetype);
            return;
        }

        elseif($action == 'removesubmission'){
            $o .= $this->view_remove_submission_confirm();
        }

        elseif ($action == 'editsubmission') {
            $o .= $this->view_editsubmission_page();
        }

        elseif ($action == 'instructorapprove') {
            if(!has_capability('mod/workflow:instructorapprove', $this->get_context())) {
                throw new required_capability_exception($this->context, 'mod/workflow:instructorapprove', 'nopermission', '');
            }
            $o .= $this->view_instructorapprove_page();
        }

        elseif ($action == 'requestreject') {
            if(!has_capability('mod/workflow:requestreject', $this->get_context())) {
                throw new required_capability_exception($this->context, 'mod/workflow:requestreject', 'nopermission', '');
            }
            $o .= $this->view_reject_request_confirm();
        }

        elseif ($action == 'lecturerapprove') {
            if(!has_capability('mod/workflow:lecturerapprove', $this->get_context())) {
                throw new required_capability_exception($this->context, 'mod/workflow:lecturerapprove', 'nopermission', '');
            }
            $o .= $this->view_lecturerapprove_page();
        }
        elseif ($action == 'grader'){
            if(!has_capability('mod/workflow:instructorapprove', $this->get_context()) and has_capability('mod/workflow:lecturerapprove', $this->get_context())) {
                throw new required_capability_exception($this->context, 'mod/workflow:instructorapprove', 'nopermission', '');
            }
            $o .= $this->view_instuructor_submission_page();
        }

        // Now show the right view page.
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
        $cm = $this->get_course_module();
        $course = $this->get_course();

        $o = '';

        $postfix = '';

        $o .= $this->get_renderer()->render(new workflow_header($instance,
            $this->get_context(),
            true,
            $this->get_course_module()->id,
            '', '', $postfix));


        $coursemodule_id = required_param('id', PARAM_ALPHANUM);
        $context = context_module::instance($coursemodule_id);
        $workflow = $this->get_workflow($DB, $coursemodule_id);

        if ($this->can_view_grades()) {

            $lecturer = $this->get_useremail($DB, $workflow->lecturer);
            $instructor = $this->get_useremail($DB, $workflow->instructor);
            $item = '';
            if ($workflow->type === 'assignment') {
                $item = $this->get_assignmentname_form_workflow($DB, $workflow->id)->name;
            } else if ($workflow->type === 'quiz') {
                $item = $this->get_quizname_form_workflow($DB, $workflow->id)->name;
            }
            $participants = 0;
            $requested = 0;
            $pending = 0;
            $lecturer = $this->get_useremail($DB, $workflow->lecturer);
            $instructor = $this->get_useremail($DB, $workflow->instructor);
            $item = '';
            if ($workflow->type==='assignment') {
                $item = $this->get_assignmentname_form_workflow($DB, $workflow->id)->name;
            } else if ($workflow->type==='quiz') {
                $item = $this->get_quizname_form_workflow($DB, $workflow->id)->name;
            }
            $countparticipants = $this->count_participants(false);
            $requested = 0;
            $pending = 0;

            // TODO: two hard coded values
            $summary = new workflow_grading_summary(
                $countparticipants,
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
                $course->relativedatesmode,
                $course->startdate,
                true,
                $cm->visible
            );

            $o .= $this->get_renderer()->render($summary);
        }

        elseif ($this->can_view_submission($USER->id)) {

            $request = $this->get_user_request($DB,  $USER->id, $workflow->id);

            $request_status = 'No attempt';
            $cansubmit = true;
            $submitteddate = 0;
            $student_comment = '-';
            $instructor_comment = '-';

            if($request !== null){
                $request_status = $request->request_status;
                $submitteddate = $request->submission_date;
                $student_comment = $request->student_comments;
                $instructor_comment = $request->instructor_comments;
            }

            $summary = new workflow_request_status(
                $workflow->allowsubmissionsfromdate,
                $request, //is null if no submission
                true,
                $request_status,
                $workflow->duedate,
                $workflow->cutoffdate,
                $submitteddate,
                $coursemodule_id,
                $student_comment
            );

            $o .= $this->get_renderer()->render($summary);

        }

        $o .= $this->view_footer();

        return $o;
    }

    public function get_useremail($DB, $user_id) {
        $user_db = $DB->get_record_sql("SELECT u.email
                                    FROM {user} u
                                    WHERE u.id = ?;", [$user_id]);

        return $user_db;
    }

    public function get_quizname_form_workflow($DB, $workflow_id) {
        $quizid = $DB->get_record_sql("SELECT q.name
                                    FROM {quiz} q
                                    WHERE q.id IN (
                                        SELECT wq.quiz
                                        FROM {workflow_quiz} wq
                                        WHERE wq.workflow = ?
                                    );
                            ", [$workflow_id]);

        return $quizid;
    }

     function get_assignmentname_form_workflow($DB, $workflow_id) {
        $assignmentid = $DB->get_record_sql("SELECT a.name
                                            FROM {assign} a
                                            WHERE a.id IN (
                                                SELECT wa.assignment
                                                FROM {workflow_assignment} wa
                                                WHERE wa.workflow = ?
                                            );
                            ", [$workflow_id]);

        return $assignmentid;
    }

    private function get_workflow($DB, $coursemodule_id) {
        $workflow = $DB->get_record_sql("SELECT *
                                        FROM {workflow} w
                                        WHERE w.id IN (
                                        SELECT cm.instance
                                        FROM {course_modules} cm
                                        WHERE cm.id=? );
                            ", [$coursemodule_id]);

        return $workflow;
    }

    private function get_user_request($DB, $uid, $wid) {

        $request = $DB->get_record_sql("SELECT * 
                                        FROM {workflow_request} wr
                                        WHERE wr.workflow = ?
                                        AND wr.student = ?",
                                        [ $wid,  $uid ]
                                        );


        if(!$request) $request = null;
        return $request;

    }


    protected function view_editsubmission_page()
    {
        global $CFG, $DB, $USER, $PAGE;
        $instance = $this->get_instance();
        $o = '';
        $postfix = '';

        require_once($CFG->dirroot . '/mod/workflow/classes/form/request_form.php');
        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $coursemodule_id = required_param('id', PARAM_ALPHANUM);

        $o .= $this->get_renderer()->render(new workflow_header($instance,
            $this->get_context(),
            true,
            $this->get_course_module()->id,
            '', '', $postfix));

        $mform = new request_form(null, array('cmid'=> $coursemodule_id));
        $form = new workflow_requestform('editsubmissionform', $mform);

        $o .= $this->get_renderer()->render($form);

        $o .= $this->view_footer();

        return $o;

    }

    /**
     * Save assignment submission.
     *
     * @return bool
     */
    protected function process_save_submission() {
        global $CFG, $USER;

        // Include submission form.
        require_once($CFG->dirroot . '/mod/workflow/classes/form/request_form.php');

        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $coursemodule_id = required_param('id', PARAM_ALPHANUM);

        require_sesskey();
        $instance = $this->get_instance();

        $mform = new request_form(null, array('cmid'=> $coursemodule_id));

        if ($mform->is_cancelled()) {
            return true;
        }
        if ($data = $mform->get_data()) {
            return $this->add_request($data,$coursemodule_id);
        }
        return false;
    }

    protected function view_instructorapprove_page()
    {
        global $CFG, $DB, $USER, $PAGE;
        $instance = $this->get_instance();
        $o = '';
        $postfix = '';

        require_once($CFG->dirroot . '/mod/workflow/classes/form/instructor_approve_form.php');
        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $coursemodule_id = required_param('id', PARAM_ALPHANUM);

        $o .= $this->get_renderer()->render(new workflow_header($instance,
            $this->get_context(),
            true,
            $this->get_course_module()->id,
            '', '', $postfix));

        $mform = new instructor_approve_form(null, array('cmid'=> $coursemodule_id));
        $form = new workflow_requestapprove('instructorapprove', $mform);

        $o .= $this->get_renderer()->render($form);

        $o .= $this->view_footer();

        return $o;

    }

    protected function view_lecturerapprove_page()
    {
        global $CFG, $DB, $USER, $PAGE;
        $instance = $this->get_instance();
        $o = '';
        $postfix = '';

        require_once($CFG->dirroot . '/mod/workflow/classes/form/lecturer_approve_form.php');
        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $coursemodule_id = required_param('id', PARAM_ALPHANUM);

        $o .= $this->get_renderer()->render(new workflow_header($instance,
            $this->get_context(),
            true,
            $this->get_course_module()->id,
            '', '', $postfix));

        $mform = new lecturer_approve_form(null, array('cmid'=> $coursemodule_id));
        $form = new workflow_requestapprove('lecturerapprove', $mform);

        $o .= $this->get_renderer()->render($form);

        $o .= $this->view_footer();

        return $o;

    }

    protected function view_instuructor_submission_page() {

        global $CFG, $DB, $USER, $PAGE;
        $instance = $this->get_instance();
        $o = '';
        $postfix = '';

        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $coursemodule_id = required_param('id', PARAM_ALPHANUM);

        $o .= $this->get_renderer()->render(new workflow_header($instance,
            $this->get_context(),
            true,
            $this->get_course_module()->id,
            '', '', $postfix));

        $workflow = $this->get_workflow($DB, $coursemodule_id);

        $request = $this->get_user_request($DB,  $USER->id, $workflow->id);

        $request_status = 'No attempt';
        $request_id = null;
        $cansubmit = true;
        $submitteddate = 0;
        $student_comment = '-';
        $instructor_comment = '-';

        if($request !== null){
            $request_status = $request->request_status;
            $request_id = $request->id;
            $submitteddate = $request->submission_date;
            $student_comment = $request->student_comments;
            $instructor_comment = $request->instructor_comments;
        }

        $summary = new workflow_request_status_instructor(
            $workflow->allowsubmissionsfromdate,
            $request_id,
            $request, //is null if no submission
            true,
            $request_status,
            $workflow->duedate,
            $workflow->cutoffdate,
            $submitteddate,
            $coursemodule_id,
            $student_comment,
            $instructor_comment
        );

        $o .= $this->get_renderer()->render($summary);

        $o .= $this->view_footer();

        return $o;

    }

    /**
     * Show a confirmation page to make sure they want to remove submission data.
     *
     * @return string
     */
    protected function view_reject_request_confirm() {
        global $USER, $DB;

        $requestid = required_param('requestid', PARAM_INT);

        $o = '';
        $header = new workflow_header($this->get_instance(),
            $this->get_context(),
            false,
            $this->get_course_module()->id);
        $o .= $this->get_renderer()->render($header);

        $urlparams = array('id' => $this->get_course_module()->id,
            'action' => 'requestrejected',
            'userid' => $USER->id,
            'requestid' => $requestid,
            'sesskey' => sesskey());
        $confirmurl = new moodle_url('/mod/workflow/view.php', $urlparams);

        $urlparams = array('id' => $this->get_course_module()->id,
            'action' => '');
        $cancelurl = new moodle_url('/mod/workflow/view.php', $urlparams);

        $confirmstr = get_string('rejectrequestconfirm', 'workflow');

        $o .= $this->get_renderer()->confirm($confirmstr,
            $confirmurl,
            $cancelurl);

        $o .= $this->view_footer();

        return $o;
    }

    /**
     * Add this request to the database.
     *
     * @param stdClass $formdata The data submitted from the form
     * @return mixed false if an error occurs or the int id of the new request
     */
    public function add_request( $formdata, $coursemodule_id){

        global $DB, $USER;

        $workflow = $this->get_workflow($DB, $coursemodule_id);
        $update = new stdClass();
        $update->workflow = $workflow->id;
        $update->student = $USER->id;
        $update->reason = $formdata->reason_select;
        $update->other_reason = $formdata->other_reason;
        $update->student_comments = $formdata->comments['text'];
        $update->student_commentsformat = $formdata->comments['format'];
        $update->extend_date = $formdata->extend_to;
        $update->submission_date = time();
        $update->request_status = get_string('pending', 'workflow');


        $returnid = $DB->insert_record('workflow_request', $update);

        return $returnid;
    }

    /**
     * Show a confirmation page to make sure they want to remove submission data.
     *
     * @return string
     */
    protected function view_remove_submission_confirm() {
        global $USER, $DB;

        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

        $o = '';
        $header = new workflow_header($this->get_instance(),
            $this->get_context(),
            false,
            $this->get_course_module()->id);
        $o .= $this->get_renderer()->render($header);

        $urlparams = array('id' => $this->get_course_module()->id,
            'action' => 'removesubmissionconfirm',
            'userid' => $userid,
            'sesskey' => sesskey());
        $confirmurl = new moodle_url('/mod/workflow/view.php', $urlparams);

        $urlparams = array('id' => $this->get_course_module()->id,
            'action' => '');
        $cancelurl = new moodle_url('/mod/workflow/view.php', $urlparams);

        if ($userid == $USER->id) {
            $confirmstr = get_string('removesubmissionconfirm', 'assign');
        } else {
            $name = $this->fullname($user);
            $confirmstr = get_string('removesubmissionconfirmforstudent', 'assign', $name);
        }
        $o .= $this->get_renderer()->confirm($confirmstr,
            $confirmurl,
            $cancelurl);

        $o .= $this->view_footer();

        //\mod_assign\event\remove_submission_form_viewed::create_from_user($this, $user)->trigger();

        return $o;
    }

    /**
     * Remove the current request.
     *
     * @param int $userid
     * @return boolean
     */
    public function process_remove_submission() {

        global $DB, $USER;
        $userid = optional_param('userid', $USER->id, PARAM_INT);
        $coursemodule_id = required_param('id', PARAM_ALPHANUM);
        $workflow = $this->get_workflow($DB, $coursemodule_id);
        $wid = $workflow->id;


        $result = true;

        $result = $DB->delete_records('workflow_request', array('student' => $userid, 'workflow' =>  $wid));

        return $result;

    }

    /**
     * Approved request by instructor.
     * Update request status
     * Add instructor comment
     *
     * @param int $userid
     * @return boolean
     */
    public function process_instructor_approve() {
        global $DB, $USER, $CFG;

        // update request status
        require_once($CFG->dirroot . '/mod/workflow/classes/form/instructor_approve_form.php');
        $requestid = required_param('requestid', PARAM_INT);
        $coursemodule_id = required_param('id', PARAM_ALPHANUM);

        require_sesskey();

        $mform = new instructor_approve_form(null, array('cmid'=> $coursemodule_id));

        if ($mform->is_cancelled()) {
            return true;
        }

        if ($data = $mform->get_data()) {
            $update_request = $DB->get_record('workflow_request', array('id'=>$requestid), '*');
            $workflow = $DB->get_record('workflow', array('id'=>$update_request->workflow), 'instructor');

            // check assigned instructor
            if ($USER->id!=$workflow->instructor) return false;

            $update_request->request_status = 'accepted';
            // TODO: save instructor comment in DB
            $DB->update_record('workflow_request', $update_request);
            return true;
        }
        return false;
    }

    /**
     * Approved request by lecturer.
     * Update request status
     * Execute automated tasks if necessary
     *
     * @param int $userid
     * @return boolean
     */
    public function process_lecturer_approve() {
        global $DB, $USER, $CFG;

        // update request status
        require_once($CFG->dirroot . '/mod/workflow/classes/form/lecturer_approve_form.php');
        $requestid = required_param('requestid', PARAM_INT);
        $coursemodule_id = required_param('id', PARAM_ALPHANUM);

        require_sesskey();

        $mform = new lecturer_approve_form(null, array('cmid'=> $coursemodule_id));

        if ($mform->is_cancelled()) {
            return true;
        }

        if ($data = $mform->get_data()) {
            $update_request = $DB->get_record('workflow_request', array('id'=>$requestid), '*');
            $workflow = $DB->get_record('workflow', array('id'=>$update_request->workflow), '*');

            // check if assigned lecturer
            if ($USER->id!=$workflow->lecturer) return false;

            $update_request->request_status = 'approved';
            $DB->update_record('workflow_request', $update_request);

            // automated tasks
            $this->run_automated_task($workflow, $data->extend_to);

            // inform student - send message
            $message = new \core\message\message();
            $message->component = 'mod_workflow'; // plugin's name
            $message->name = 'requeststatusupdate'; // notification name from message.php
            $message->userfrom = core_user::get_noreply_user();
            $message->userto = $DB->get_record('user', array('id' => $update_request->student));
            $message->subject = 'Workflow Request Approve Notification';

            $message->fullmessageformat = FORMAT_MARKDOWN;
            $messageBody = '';
            $messageBody .= '<h1>Request Details</h1>';
            $messageBody .= '<p><strong>Workflow:</strong> ' . $workflow->name . '</p>';
            $messageBody .= '<p><strong>Reason:</strong> ' . $update_request->reason . '</p>';
            $messageBody .= $update_request->student_comments . '<hr>';
            $message->fullmessagehtml = $messageBody;
            $message->smallmessage = 'Your request on ' . $workflow->name . ' has been approved';
            $message->notification = 1; // this is a notification generated from Moodle

            // Actually send the message
            $messageid = message_send($message);


            return true;
        }
        return false;
    }

    /**
     * Run automated task based on workflow type.
     *
     * @param Workflow $workflow
     * @param Date $extend_to
     * @return boolean
     */
    private function run_automated_task($workflow, $extend_to) {
        global $DB;

        if ($workflow->type=='assignment') {
            $assignment_workflow = $DB->get_record('workflow_assignment', array('workflow'=>$workflow->id), 'assignment');
            $assignment = $DB->get_record('assign', array('id'=>$assignment_workflow->assignment), '*');

            if ($assignment->duedate>0 && $assignment->duedate<$extend_to) {
                $assignment->duedate = $extend_to;
            }
            if ($assignment->cutoffdate>0 && $assignment->cutoffdate<$extend_to) {
                $assignment->cutoffdate = $extend_to;
            }
            $DB->update_record('assign', $assignment);

        } else if ($workflow->type=='quiz') {
            $quiz_workflow = $DB->get_record('workflow_quiz', array('workflow'=>$workflow->id), 'quiz');
            $quiz = $DB->get_record('quiz', array('id'=>$quiz_workflow->quiz), '*');

            if ($quiz->timeclose>0 && $quiz->timeclose<$extend_to) {
                $quiz->timeclose = $extend_to;
            }
            $DB->update_record('quiz', $quiz);
        }
        return true;
    }

    /**
     * Rejected request by instructor.
     * Update request status
     *
     * @param int $userid
     * @return boolean
     */
    public function process_request_reject() {
        global $DB, $USER;

        $requestid = required_param('requestid', PARAM_INT);
        $update_request = $DB->get_record('workflow_request', array('id'=>$requestid), '*');
        $workflow = $DB->get_record('workflow', array('id'=>$update_request->workflow), '*');

        $result = null;
        // check assigned instructor/lecturer
        if ($USER->id==$workflow->lecturer || $USER->id==$workflow->instructor) {
            // update request status
            $update_request->request_status = 'declined';
            $result = $DB->update_record('workflow_request', $update_request);


            // inform student - send message
            $message = new \core\message\message();
            $message->component = 'mod_workflow'; // plugin's name
            $message->name = 'requeststatusupdate'; // notification name from message.php
            $message->userfrom = core_user::get_noreply_user();
            $message->userto = $DB->get_record('user', array('id' => $update_request->student));
            $message->subject = 'Workflow Request Reject Notification';

            $message->fullmessageformat = FORMAT_MARKDOWN;
            $messageBody = '';
            $messageBody .= '<h1>Request Details</h1>';
            $messageBody .= '<p><strong>Workflow:</strong> ' . $workflow->name . '</p>';
            $messageBody .= '<p><strong>Reason:</strong> ' . $update_request->reason . '</p>';
            $messageBody .= $update_request->student_comments . '<hr>';
            $message->fullmessagehtml = $messageBody;
            $message->smallmessage = 'Your request on ' . $workflow->name . ' has been rejected';
            $message->notification = 1; // this is a notification generated from Moodle

            // Actually send the message
            $messageid = message_send($message);
        }
        return  $result;
    }

    /**
     * Does this user have view grade or grade permission for this assignment?
     *
     * @param mixed $groupid int|null when is set to a value, use this group instead calculating it
     * @return bool
     */
    public function can_view_grades($groupid = null) {
        // Permissions check.
        if (!has_any_capability(array('mod/assign:viewgrades', 'mod/assign:grade'), $this->context)) {
            return false;
        }

        return true;
    }

    /**
     * Perform an access check to see if the current $USER can view this users submission.
     *
     * @param int $userid
     * @return bool
     */
    public function can_view_submission($userid) {
        global $USER;

        if (!$this->is_active_user($userid) && !has_capability('moodle/course:viewsuspendedusers', $this->context)) {
            return false;
        }
        if (!is_enrolled($this->get_course_context(), $userid)) {
            return false;
        }
        if (has_any_capability(array('mod/assign:viewgrades', 'mod/assign:grade'), $this->context)) {
            return true;
        }
        if ($userid == $USER->id) {
            return true;
        }
        return false;
    }

    /**
     * Return true is user is active user in course else false
     *
     * @param int $userid
     * @return bool true is user is active in course.
     */
    public function is_active_user($userid) {
        return !in_array($userid, get_suspended_userids($this->context, true));
    }
}