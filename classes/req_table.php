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
 * Version details
 *
 * @package    mod_workflow
 * @copyright  2022 SEP15
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_workflow;

//use stdClass;
use dml_exception;

class req_table
{
    public function getAllRequests()
    {
        global $DB;
        try {
            return $DB->get_records('workflow_request');
        } catch (dml_exception $e) {
            return [];
        }
    }

    public function getWorkflow(string $id)
    {
        global $DB;
        return $DB->get_record(
            'workflow',
            [
                'id' => $id
            ]
        );
    }

    public function getWorkflowbyCMID($cmid){
        global $DB;
        $sql = 'id=:cmid';
        $params = [
            'cmid'=>$cmid
        ];
        $workflowid = $DB->get_field_select('course_modules', 'instance', $sql, $params);
        $workflow = $this->getWorkflow($workflowid);
        return $workflow;
    }

    public function getInstructor($workflowid){
        global $DB;
        $sql = 'id=:id';
        $params = [
            'id'=>$workflowid
        ];
        return $DB->get_field_select('workflow', 'instructor', $sql, $params);
    }
    public function getLecturer($workflowid){
        global $DB;
        $sql = 'id=:id';
        $params = [
            'id'=>$workflowid
        ];
        return $DB->get_field_select('workflow', 'lecturer', $sql, $params);
    }
    public function getUsername($studentid){
        global $DB;
        $sql = 'id=:id';
        $params = [
            'id'=>$studentid
        ];
        return $DB->get_field_select('user', 'username', $sql, $params);
    }

    public function getRequestsByWorkflow($cmid)
    {
        global $DB;
        return $DB->get_records_select('workflow_request', 'workflow = :workflow', [
            'workflow' => $cmid
        ]);
    }

    public function processRequests($requests)
    {
        foreach ($requests as $request) {
            $stuid = $request->student;
            $request->request_status = ucwords($request->request_status);
            $request->submission_date = date("Y-m-d H:i:s", $request->submission_date);
            $request->student = $this->getUsername($stuid);
        }
        return $requests;
    }

    public function getValidRequestsByWorkflow($workflowid)
    {
        global $DB;
        return $DB->get_records_select('workflow_request', 'workflow = :workflow and request_status=:request_status', [
            'workflow' => $workflowid,
            'request_status' => 'accepted',
        ]);
    }
}
