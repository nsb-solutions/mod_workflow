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

class request_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition()
    {
        global $CFG;
        global $PAGE;

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/workflow/module.js'));

        $mform = $this->_form;

        //Adding the select reason field
        $reasons = array(
            'medical' => get_string('medical', 'workflow'),
            'university related' => get_string('university_related', 'workflow'),
            'forgot' => get_string('forgot', 'workflow'),
            'other' => get_string('other', 'workflow')
        );
        $reasons_select = $mform->addElement('select', 'reason_select', get_string('reason', 'workflow'), $reasons);
        $mform->addHelpButton('reason_select', 'reason', 'workflow');
        $reasons_select->setSelected($reasons);

        //if other reason selected
        $mform->addElement('text', 'other_reason', get_string('reason', 'workflow'), array('size' => '64'));
        $mform->setType('other_reason', PARAM_ALPHA);
        //$mform->addHelpButton('reason_select', 'other', 'workflow');

        //Adding editor field to input any comments //TODO db
        $mform->addElement('editor', 'comments', get_string('comments', 'workflow'));
        $mform->setType('comments', PARAM_RAW);

        //Adding file upload field
        $mform->addElement('filemanager', 'attachments', get_string('evidence', 'workflow'), null,
            array('subdirs' => 0, 'maxbytes' => 102400, 'areamaxbytes' => 10485760, 'maxfiles' => 50,
                'accepted_types' => array('.doc', '.pdf', '.jpg', '.png', '.jpeg'), 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL)); //TODO get file types form db

        $mform->addElement('date_time_selector', 'extend_to', get_string('extend_to', 'workflow'));

        $this->add_action_buttons();
    }
}