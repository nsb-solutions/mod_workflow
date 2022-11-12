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

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable header
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow_header implements renderable {
    /** @var stdClass the assign record  */
    public $assign = null;
    /** @var mixed context|null the context record  */
    public $context = null;
    /** @var bool $showintro - show or hide the intro */
    public $showintro = false;
    /** @var int coursemoduleid - The course module id */
    public $coursemoduleid = 0;
    /** @var string $subpage optional subpage (extra level in the breadcrumbs) */
    public $subpage = '';
    /** @var string $preface optional preface (text to show before the heading) */
    public $preface = '';
    /** @var string $postfix optional postfix (text to show after the intro) */
    public $postfix = '';

    /**
     * Constructor
     *
     * @param stdClass $assign  - the assign database record
     * @param mixed $context context|null the course module context
     * @param bool $showintro  - show or hide the intro
     * @param int $coursemoduleid  - the course module id
     * @param string $subpage  - an optional sub page in the navigation
     * @param string $preface  - an optional preface to show before the heading
     */
    public function __construct(stdClass $assign,
                                         $context,
                                         $showintro,
                                         $coursemoduleid,
                                         $subpage='',
                                         $preface='',
                                         $postfix='') {
        $this->assign = $assign;
        $this->context = $context;
        $this->showintro = $showintro;
        $this->coursemoduleid = $coursemoduleid;
        $this->subpage = $subpage;
        $this->preface = $preface;
        $this->postfix = $postfix;
    }
}


/**
 * Renderable grading summary
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow_grading_summary implements renderable {
    /** @var int participantcount - The number of users who can submit to this assignment */
    public $participantcount = 0;
    /** @var string type - Type of workflow */
    public $type = '';
    /** @var string name - name of assigned assignment/quiz */
    public $name = '';
    /** @var bool submissionsenabled - Allow submissions */
    public $submissionsenabled = false;
    /** @var int submissionssubmittedcount - The number of submissions in submitted status */
    public $submissionssubmittedcount = 0;
    /** @var int submissionsneedgradingcount - The number of submissions that need grading */
    public $submissionsneedgradingcount = 0;
    /** @var int duedate - The assignment due date (if one is set) */
    public $duedate = 0;
    /** @var int cutoffdate - The assignment cut off date (if one is set) */
    public $cutoffdate = 0;
    /** @var int coursemoduleid - The assignment course module id */
    public $coursemoduleid = 0;
    /** @var string lecturer - Lecturer name assigned to workflow */
    public $lecturer = '';
    /** @var string instructor - Instructor name assigned to workflow */
    public $instructor = '';
    /** @var boolean relativedatesmode - Is the course a relative dates mode course or not */
    public $courserelativedatesmode = false;
    /** @var int coursestartdate - start date of the course as a unix timestamp*/
    public $coursestartdate;
    /** @var boolean cangrade - Can the current user grade students? */
    public $cangrade = false;
    /** @var boolean isvisible - Is the assignment's context module visible to students? */
    public $isvisible = true;

    /** @var string no warning needed about group submissions */
    const WARN_GROUPS_NO = false;
    /** @var string warn about group submissions, as groups are required */
    const WARN_GROUPS_REQUIRED = 'warnrequired';
    /** @var string warn about group submissions, as some will submit as 'Default group' */
    const WARN_GROUPS_OPTIONAL = 'warnoptional';

    /**
     * constructor
     *
     * @param int $participantcount
     * @param string $type
     * @param string $name
     * @param bool $submissionsenabled
     * @param int $submissionssubmittedcount
     * @param int $cutoffdate
     * @param int $duedate
     * @param int $coursemoduleid
     * @param int $submissionsneedgradingcount
     * @param string $lecturer
     * @param string $instructor
     * @param bool $courserelativedatesmode true if the course is using relative dates, false otherwise.
     * @param int $coursestartdate unix timestamp representation of the course start date.
     * @param bool $cangrade
     * @param bool $isvisible
     */
    public function __construct($participantcount,
                                $type,
                                $name,
                                $submissionsenabled,
                                $submissionssubmittedcount,
                                $cutoffdate,
                                $duedate,
                                $coursemoduleid,
                                $submissionsneedgradingcount,
                                $lecturer,
                                $instructor,
                                $courserelativedatesmode,
                                $coursestartdate,
                                $cangrade = true,
                                $isvisible = true) {
        $this->participantcount = $participantcount;
        $this->type = $type;
        $this->name = $name;
        $this->submissionsenabled = $submissionsenabled;
        $this->submissionssubmittedcount = $submissionssubmittedcount;
        $this->duedate = $duedate;
        $this->cutoffdate = $cutoffdate;
        $this->coursemoduleid = $coursemoduleid;
        $this->submissionsneedgradingcount = $submissionsneedgradingcount;
        $this->lecturer = $lecturer;
        $this->instructor = $instructor;
        $this->courserelativedatesmode = $courserelativedatesmode;
        $this->coursestartdate = $coursestartdate;
        $this->cangrade = $cangrade;
        $this->isvisible = $isvisible;
    }
}



/**
 * Renderable request status
 * @package   mod_workflow
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow_request_status implements renderable {

    /** @var int allowsubmissionsfromdate */
    public $allowsubmissionsfromdate = 0;
    /** @var stdClass the submission info (may be null) */
    public $submission = null;
    /** @var bool submissionsenabled */
    public $submissionsenabled = false;
    /** @var bool approved */
    public $approved = false;
    /** @var bool declined */
    public $declined = false;
    /** @var int duedate */
    public $duedate = 0;
    /** @var int cutoffdate */
    public $cutoffdate = 0;
    /** @var int submitteddate */
    public $submitteddate = 0;
    /** @var int coursemoduleid - The workflow course module id */
    public $coursemoduleid = 0;

    /**
     * Constructor
     *
     * @param int $allowsubmissionsfromdate
     * @param stdClass $submission
     * @param bool $submissionsenabled
     * @param bool $approved
     * @param bool $declined
     * @param int $duedate
     * @param int $cutoffdate
     * @param int $submitteddate
     * @param int $coursemoduleid
     */
    public function __construct($allowsubmissionsfromdate,
                                $submission,
                                $submissionsenabled,
                                $approved,
                                $declined,
                                $duedate,
                                $cutoffdate,
                                $submitteddate,
                                $coursemoduleid)
    {
        $this->allowsubmissionsfromdate = $allowsubmissionsfromdate;
        $this->submission = $submission;
        $this->submissionsenabled = $submissionsenabled;
        $this->approved = $approved;
        $this->declined = $declined;
        $this->duedate = $duedate;
        $this->cutoffdate = $cutoffdate;
        $this->submitteddate = $submitteddate;
        $this->coursemoduleid = $coursemoduleid;
    }


}

/**
 * Implements a renderable grading options form
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow_requestform implements renderable {
    /** @var moodleform $form is the edit submission form */
    public $form = null;
    /** @var string $classname is the name of the class to assign to the container */
    public $classname = '';
    /** @var string $jsinitfunction is an optional js function to add to the page requires */
    public $jsinitfunction = '';

    /**
     * Constructor
     * @param string $classname This is the class name for the container div
     * @param moodleform $form This is the moodleform
     * @param string $jsinitfunction This is an optional js function to add to the page requires
     */

    public function __construct($classname, moodleform $form, $jsinitfunction = '') {
        $this->classname = $classname;
        $this->form = $form;
        $this->jsinitfunction = $jsinitfunction;
    }

}

/**
 * Implements a renderable grading options form
 * @package   mod_workflow
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow_requestapprove implements renderable {
    /** @var moodleform $form is the edit submission form */
    public $form = null;
    /** @var string $classname is the name of the class to assign to the container */
    public $classname = '';
    /** @var string $jsinitfunction is an optional js function to add to the page requires */
    public $jsinitfunction = '';

    /**
     * Constructor
     * @param string $classname This is the class name for the container div
     * @param moodleform $form This is the moodleform
     * @param string $jsinitfunction This is an optional js function to add to the page requires
     */

    public function __construct($classname, moodleform $form, $jsinitfunction = '') {
        $this->classname = $classname;
        $this->form = $form;
        $this->jsinitfunction = $jsinitfunction;
    }

}