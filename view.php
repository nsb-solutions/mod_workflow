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
 * Prints an instance of workflow.
 *
 * @package     workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot . '/mod/workflow/locallib.php');

// Course module id.
$id = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'workflow');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/workflow:view', $context);

$workflow = new workflow($context, $cm, $course);
$urlparams = array('id' => $id,
    'action' => optional_param('action', '', PARAM_ALPHA));

$url = new moodle_url('/mod/workflow/view.php', $urlparams);
$PAGE->set_url($url);

// Update module completion status.
$workflow->set_module_viewed();

// Get the assign class to
// render the page.
echo $workflow->view(optional_param('action', '', PARAM_ALPHA));