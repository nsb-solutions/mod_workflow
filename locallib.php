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
    private $requestinstances = [];

    /** @var context the context of the course module for this workflow instance
     *               (or just the course if we are creating a new one)
     */
    private $context;

    /** @var stdClass the course this workflow instance belongs to */
    private $course;

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
            $update_other = $DB->get_record('workflow_quiz', array('workflow'=>$formdata->instance), '*');
            $result_other = $DB->update_record('workflow_other', $update_other);
        }

        return $result;
    }
}