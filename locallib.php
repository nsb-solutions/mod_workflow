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

require_once($CFG->dirroot . '/mod/workflow/submissionplugin.php');
require_once($CFG->dirroot . '/mod/workflow/mod_form.php');

/**
 * Module instance settings form.
 *
 * @package     workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class workflow {
//    /** @var array list of the installed submission plugins */
//    private $submissionplugins;
//
//    /** @var stdClass the assignment record that contains the global settings for this assign instance */
//    private $instance;
//
//    /** @var context the context of the course module for this assign instance
//     *               (or just the course if we are creating a new one)
//     */
//    private $context;
//
//    /** @var stdClass the course this assign instance belongs to */
//    private $course;
//
//    /** @var cm_info the course module for this assign instance */
//    private $coursemodule;
//
//    /** @var array cache for things like the coursemodule name or the scale menu -
//     *             only lives for a single request.
//     */
//    private $cache;
//
//    /** @var string A key used to identify userlists created by this object. */
//    private $useridlistid = null;
//
//    public function __construct($coursemodulecontext, $coursemodule, $course) {
//        global $SESSION;
//
//        $this->context = $coursemodulecontext;
//        $this->course = $course;
//
//        // Ensure that $this->coursemodule is a cm_info object (or null).
//        $this->coursemodule = cm_info::create($coursemodule);
//
//        // Temporary cache only lives for a single request - used to reduce db lookups.
//        $this->cache = array();
//
//        $this->submissionplugins = $this->load_plugins('assignsubmission');
//
//        // Extra entropy is required for uniqid() to work on cygwin.
//        $this->useridlistid = clean_param(uniqid('', true), PARAM_ALPHANUM);
//
//        if (!isset($SESSION->mod_assign_useridlist)) {
//            $SESSION->mod_assign_useridlist = [];
//        }
//    }
//
//    /**
//     * Set the course data.
//     *
//     * @param stdClass $course The course data
//     */
//    public function set_course(stdClass $course) {
//        $this->course = $course;
//    }
//
//    /**
//     * Get list of submission plugins installed.
//     *
//     * @return array
//     */
//    public function get_submission_plugins() {
//        return $this->submissionplugins;
//    }
//
//    /**
//     * Add one plugins settings to edit plugin form.
//     *
//     * @param assign_plugin $plugin The plugin to add the settings from
//     * @param MoodleQuickForm $mform The form to add the configuration settings to.
//     *                               This form is modified directly (not returned).
//     * @param array $pluginsenabled A list of form elements to be added to a group.
//     *                              The new element is added to this array by this function.
//     * @return void
//     */
//    protected function add_plugin_settings(assign_plugin $plugin, MoodleQuickForm $mform, & $pluginsenabled) {
//        global $CFG;
//        if ($plugin->is_visible() && !$plugin->is_configurable() && $plugin->is_enabled()) {
//            $name = $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled';
//            $pluginsenabled[] = $mform->createElement('hidden', $name, 1);
//            $mform->setType($name, PARAM_BOOL);
//            $plugin->get_settings($mform);
//        } else if ($plugin->is_visible() && $plugin->is_configurable()) {
//            $name = $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled';
//            $label = $plugin->get_name();
//            $pluginsenabled[] = $mform->createElement('checkbox', $name, '', $label);
//            $helpicon = $this->get_renderer()->help_icon('enabled', $plugin->get_subtype() . '_' . $plugin->get_type());
//            $pluginsenabled[] = $mform->createElement('static', '', '', $helpicon);
//
//            $default = get_config($plugin->get_subtype() . '_' . $plugin->get_type(), 'default');
//            if ($plugin->get_config('enabled') !== false) {
//                $default = $plugin->is_enabled();
//            }
//            $mform->setDefault($plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled', $default);
//
//            $plugin->get_settings($mform);
//
//        }
//    }
//
//    /**
//     * Add settings to edit plugin form.
//     *
//     * @param MoodleQuickForm $mform The form to add the configuration settings to.
//     *                               This form is modified directly (not returned).
//     * @return void
//     */
//    public function add_all_plugin_settings(MoodleQuickForm $mform) {
//        $mform->addElement('header', 'submissiontypes', get_string('submissiontypes', 'assign'));
//
//        $submissionpluginsenabled = array();
//        $group = $mform->addGroup(array(), 'submissionplugins', get_string('submissiontypes', 'assign'), array(' '), false);
//        foreach ($this->submissionplugins as $plugin) {
//            $this->add_plugin_settings($plugin, $mform, $submissionpluginsenabled);
//        }
//        $group->setElements($submissionpluginsenabled);
//
//        $mform->addElement('header', 'feedbacktypes', get_string('feedbacktypes', 'assign'));
//        $feedbackpluginsenabled = array();
//        $group = $mform->addGroup(array(), 'feedbackplugins', get_string('feedbacktypes', 'assign'), array(' '), false);
//        foreach ($this->feedbackplugins as $plugin) {
//            $this->add_plugin_settings($plugin, $mform, $feedbackpluginsenabled);
//        }
//        $group->setElements($feedbackpluginsenabled);
//        $mform->setExpanded('submissiontypes');
//    }
}