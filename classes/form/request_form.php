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

    public function definition() {

            global $CFG;

            $mform = $this->_form; // Don't forget the underscore!

            $mform->addElement('text', 'email', get_string('email'));
            $mform->setType('email', PARAM_NOTAGS);
            $mform->setDefault('email', 'Please enter email');

        }

        //Custom validation should be added here
        function validation($data, $files) {
            return array();
        }

}