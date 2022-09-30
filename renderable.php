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
    /** @var bool submissiondraftsenabled - Allow submission drafts */
    public $submissiondraftsenabled = false;
    /** @var int submissiondraftscount - The number of submissions in draft status */
    public $submissiondraftscount = 0;
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
    /** @var boolean teamsubmission - Are team submissions enabled for this assignment */
    public $teamsubmission = false;
    /** @var boolean warnofungroupedusers - Do we need to warn people that there are users without groups */
    public $warnofungroupedusers = false;
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
     * @param bool $submissiondraftsenabled
     * @param int $submissiondraftscount
     * @param bool $submissionsenabled
     * @param int $submissionssubmittedcount
     * @param int $cutoffdate
     * @param int $duedate
     * @param int $coursemoduleid
     * @param int $submissionsneedgradingcount
     * @param bool $teamsubmission
     * @param string $warnofungroupedusers
     * @param bool $courserelativedatesmode true if the course is using relative dates, false otherwise.
     * @param int $coursestartdate unix timestamp representation of the course start date.
     * @param bool $cangrade
     * @param bool $isvisible
     */
    public function __construct($participantcount,
                                $submissiondraftsenabled,
                                $submissiondraftscount,
                                $submissionsenabled,
                                $submissionssubmittedcount,
                                $cutoffdate,
                                $duedate,
                                $coursemoduleid,
                                $submissionsneedgradingcount,
                                $teamsubmission,
                                $warnofungroupedusers,
                                $courserelativedatesmode,
                                $coursestartdate,
                                $cangrade = true,
                                $isvisible = true) {
        $this->participantcount = $participantcount;
        $this->submissiondraftsenabled = $submissiondraftsenabled;
        $this->submissiondraftscount = $submissiondraftscount;
        $this->submissionsenabled = $submissionsenabled;
        $this->submissionssubmittedcount = $submissionssubmittedcount;
        $this->duedate = $duedate;
        $this->cutoffdate = $cutoffdate;
        $this->coursemoduleid = $coursemoduleid;
        $this->submissionsneedgradingcount = $submissionsneedgradingcount;
        $this->teamsubmission = $teamsubmission;
        $this->warnofungroupedusers = $warnofungroupedusers;
        $this->courserelativedatesmode = $courserelativedatesmode;
        $this->coursestartdate = $coursestartdate;
        $this->cangrade = $cangrade;
        $this->isvisible = $isvisible;
    }
}
