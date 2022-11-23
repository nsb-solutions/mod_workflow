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
 * Library of interface functions and constants.
 *
 * @package     workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function workflow_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the workflow into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_workflow_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function workflow_add_instance($moduleinstance, $mform = null) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/workflow/locallib.php');

    $workflow = new workflow(context_module::instance($moduleinstance->coursemodule), null, null);

    return $workflow->add_instance($moduleinstance);
}

/**
 * Updates an instance of the workflow in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_workflow_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function workflow_update_instance($moduleinstance, $mform = null) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/workflow/locallib.php');

    $context = context_module::instance($moduleinstance->coursemodule);
    $workflow = new workflow($context, null, null);
    return $workflow->update_instance($moduleinstance);
}

/**
 * Removes an instance of the workflow from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function workflow_delete_instance($id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/workflow/locallib.php');

    $cm = get_coursemodule_from_instance('workflow', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $workflow = new workflow($context, null, null);

    return $workflow->delete_instance($id);
}
