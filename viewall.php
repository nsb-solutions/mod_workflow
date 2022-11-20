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
 * Prints an instance of mod_workflow.
 *
 * @package     mod_workflow
 * @copyright   2022 SEP15
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//use mod_workflow\table\requests;
use mod_workflow\req_table;

// require('../../config.php');
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/workflow/locallib.php');
require_once($CFG->dirroot . '/course/format/lib.php');
//require_once($CFG->dirroot . '/mod/workflow/view.php');
require_login();


global $USER, $DB;

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'workflow');
$instance = $DB->get_record('workflow', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$workflow = $DB->get_record('workflow', ['id' => $cm->instance]);

$PAGE->set_url(new moodle_url('/mod/workflow/viewall.php'));
$PAGE->set_context($context);
$PAGE->set_title($course->shortname . ': ' . $workflow->name);
$PAGE->set_heading($workflow->name);
$PAGE->set_cm($cm, $course);


echo $OUTPUT->header();

//$request_manager = new request();
$req_table = new req_table();
//$workflow_manager = $_SESSION['workflow'];
$requests = $req_table->getAllRequests();
$cmid = $cm->id;

$workflowid = $req_table->getWorkflowbyCMID($cmid)->id;

if(has_capability('mod/workflow:lecturerapprove', $context)) {
    $workflowid = $req_table->getWorkflowbyCMID($cmid)->id;
    $lecturer = $req_table->getLecturer($workflowid);
//    $workflow_cur = $req_table->getWorkflow($workflowid);
    if ($USER->id === $lecturer) {
        $requests = $req_table->getValidRequestsByWorkflow($workflowid);
        $requests = $req_table->processRequests($requests);
        $templatecontext = (object)[
            'requests' => array_values($requests),
            'text' => 'text',
            'url' => $CFG->wwwroot . '/mod/workflow/view.php?id=' . $id . '&action=grader',
            'approveurl' => $CFG->wwwroot . '/mod/workflow/view.php?id=' . $id . '&action=lecturerapprove',
            'declineurl' => $CFG->wwwroot . '/mod/workflow/view.php?id=' . $id . '&action=requestreject',
            'cmid' => $cm->id,
            'workflow' => $workflow->name,
        ];
        echo $OUTPUT->render_from_template('mod_workflow/requests_lecturer', $templatecontext);
    } else {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id, 'You are not assigned to this workflow', null, \core\output\notification::NOTIFY_ERROR);
    }
} elseif (has_capability('mod/workflow:instructorapprove', $context)){
    $workflowid = $req_table->getWorkflowbyCMID($cmid)->id;
    $instructor = $req_table->getInstructor($workflowid);
    if ($USER->id === $instructor) {
        $requests = $req_table->getRequestsByWorkflow($workflowid);
        $requests = $req_table->processRequests($requests);
        $templatecontext = (object)[
            'requests' => array_values($requests),
            'text' => 'text',
            'url' => $CFG->wwwroot . '/mod/workflow/view.php?id=' . $id . '&action=grader',
            'approveurl' => $CFG->wwwroot . '/mod/workflow/view.php?id=' . $id . '&action=instructorapprove',
            'declineurl' => $CFG->wwwroot . '/mod/workflow/view.php?id=' . $id . '&action=requestreject',
            'cmid' => $cm->id,
            'workflow' => $workflow->name,
        ];
        echo $OUTPUT->render_from_template('mod_workflow/requests_instructor', $templatecontext);
    } else {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $course->id, 'You are not assigned to this workflow', null, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->footer();