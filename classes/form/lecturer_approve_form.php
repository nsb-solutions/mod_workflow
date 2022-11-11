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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class lecturer_approve_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition()
    {
        global $CFG;
        global $PAGE;

        $requestid = required_param('requestid', PARAM_INT);

        $mform = $this->_form;
        $cmid = $this->_customdata;
        $coursemoduleid =  $cmid['cmid'];

        $mform->addElement('date_time_selector', 'extend_to', get_string('extend_to', 'workflow'));

        $mform->addElement('hidden', 'id', $coursemoduleid);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'requestid', $requestid);
        $mform->setType('requestid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'lecturerapproved');
        $mform->setType('action', PARAM_ALPHA);

        $this->add_action_buttons();

    }

    public function validation($data, $files){
        $errors = parent::validation($data, $files);
        return $errors;
    }
}