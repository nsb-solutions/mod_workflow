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
 * Plugin strings are defined here.
 *
 * @package     workflow
 * @category    string
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Workflow';
$string['workflowname'] = 'Workflow name';
$string['workflowname_help'] = 'This will help you to automate your workflow';
$string['modulename'] = 'Moodle Plugin for Establishing Workflow';
$string['modulenameplural'] = 'Moodle Plugin for Establishing Workflows';
$string['pluginadministration'] = 'Plugin Administration';

$string['assignment'] = 'Assignment';
$string['quiz'] = 'Quiz';
$string['other'] = 'Other';
$string['workflowtype'] = 'Workflow type';
$string['workflowtype_help'] = 'Select workflow type here';


$string['pluginadministration'] = 'plugin admin';  //TODO
$string['reason'] = 'Reason of request';
$string['reason_help'] = 'Select reason of request here';
$string['medical'] = 'Medical';
$string['university_related'] = 'University related';
$string['forgot'] = 'Forgot';
$string['other'] = 'Other';
$string['other_help'] = 'Briefly state the reason';
$string['comments'] = 'Comments';
$string['evidence'] = 'Evidence';
$string['extend_to'] = 'Extend to';
$string['noattempt'] = 'No attempt';
$string['submissionstatus'] = 'Submission status';
$string['submittedforgrading'] = 'Submitted for grading';
$string['approvalstatus'] = 'Approval status';
$string['pending'] = 'Pending';
$string['approved'] = 'Approved';
$string['declined'] = 'Declined';
$string['duedate'] = 'Due date';
$string['timeremaining'] = 'Time remaining';
$string['lastmodified'] = 'Last modified';
$string['filesubmission'] = 'File submission';

$string['duedatevalidation'] = 'Due date cannot be earlier than the allow submissions from date.';
$string['cutoffdatevalidation'] = 'Cut-off date cannot be earlier than the due date.';
$string['cutoffdatefromdatevalidation'] = 'Cut-off date cannot be earlier than the allow submissions from date.';
$string['workflowtypeselectvalidation'] = 'Please select a valid workflow type.';
$string['assignmentselectvalidation'] = 'An assignment must be selected.';
$string['quizselectvalidation'] = 'A quiz must be selected.';
$string['lecturerselectvalidation'] = 'A lecturer must be selected.';
$string['instructorselectvalidation'] = 'An instructor must be selected.';

$string['rejectrequestconfirm'] = 'Are you sure you want to reject the student request?';
$string['messageprovider:requeststatusupdate'] = 'Status update of your workflow request';