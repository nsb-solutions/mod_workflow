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
 * A custom renderer class that extends the plugin_renderer_base and is used by the assign module.
 *
 * @package     workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the assign module.
 *
 * @package     workflow
 * @copyright   2022 NSB<nsb.software.lk@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_workflow_renderer extends plugin_renderer_base
{
    /**
     * Page is done - render the footer.
     *
     * @return void
     */
    public function render_footer()
    {
        return $this->output->footer();
    }

    /**
     * Render the header.
     *
     * @param assign_header $header
     * @return string
     */

    public function render_workflow_header(workflow_header $header)
    {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
            $args = ['contextname' => $header->context->get_context_name(false, true), 'subpage' => $header->subpage];
            $title = get_string('subpagetitle', 'assign', $args);
        } else {
            $title = $header->context->get_context_name(false, true);
        }
        $courseshortname = $header->context->get_course_context()->get_context_name(false, true);
        $title = $courseshortname . ': ' . $title;
        $heading = format_string($header->assign->name, false, array('context' => $header->context));

        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $o .= $this->output->heading($heading);
        if ($header->preface) {
            $o .= $header->preface;
        }

        if ($header->showintro) {
            $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $o .= format_module_intro('assign', $header->assign, $header->coursemoduleid);
            $o .= $header->postfix;
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Utility function to add a row of data to a table with 2 columns where the first column is the table's header.
     * Modified the table param and does not return a value.
     *
     * @param html_table $table The table to append the row of data to
     * @param string $first The first column text
     * @param string $second The second column text
     * @param array $firstattributes The first column attributes (optional)
     * @param array $secondattributes The second column attributes (optional)
     * @return void
     */
    private function add_table_row_tuple(html_table $table, $first, $second, $firstattributes = [],
                                                    $secondattributes = [])
    {
        $row = new html_table_row();
        $cell1 = new html_table_cell($first);
        $cell1->header = true;
        if (!empty($firstattributes)) {
            $cell1->attributes = $firstattributes;
        }
        $cell2 = new html_table_cell($second);
        if (!empty($secondattributes)) {
            $cell2->attributes = $secondattributes;
        }
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }

    /**
     * Render a table containing the current status of the grading process.
     *
     * @param assign_grading_summary $summary
     * @return string
     */
    public function render_workflow_grading_summary(workflow_grading_summary $summary)
    {
        // Create a table for the data.
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->heading('Requests summary', 3);
        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new html_table();

        // Visibility Status.
        $cell1content = get_string('hiddenfromstudents');
        $cell2content = (!$summary->isvisible) ? get_string('yes') : get_string('no');
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Workflow type.
        $cell1content = 'Workflow type';
        $cell2content = $summary->type;
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Item name.
        if (!empty($summary->name)) {
            $cell1content = 'Assigned for';
            $cell2content = $summary->name;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        // Assigned lecturer.
        $cell1content = 'Assigned lecturer';
        $cell2content = $summary->lecturer;
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Assigned instructor.
        $cell1content = 'Assigned instructor';
        $cell2content = $summary->instructor;
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Status.
        $cell1content = get_string('numberofparticipants', 'assign');
        $cell2content = $summary->participantcount;
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Submitted for grading.
        if ($summary->submissionsenabled) {
            $cell1content = 'Requested';
            $cell2content = $summary->submissionssubmittedcount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);


            $cell1content = 'Needs approval';
            $cell2content = $summary->submissionsneedgradingcount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

        }

        $time = time();
        if ($summary->duedate) {
            // Due date.
            $cell1content = get_string('duedate', 'assign');
            $duedate = $summary->duedate;
            if ($summary->courserelativedatesmode) {
                // Returns a formatted string, in the format '10d 10h 45m'.
                $diffstr = get_time_interval_string($duedate, $summary->coursestartdate);
                if ($duedate >= $summary->coursestartdate) {
                    $cell2content = get_string('relativedatessubmissionduedateafter', 'mod_assign',
                        ['datediffstr' => $diffstr]);
                } else {
                    $cell2content = get_string('relativedatessubmissionduedatebefore', 'mod_assign',
                        ['datediffstr' => $diffstr]);
                }
            } else {
                $cell2content = userdate($duedate);
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            // Time remaining.
            $cell1content = get_string('timeremaining', 'assign');
            if ($summary->courserelativedatesmode) {
                $cell2content = get_string('relativedatessubmissiontimeleft', 'mod_assign');
            } else {
                if ($duedate - $time <= 0) {
                    $cell2content = get_string('assignmentisdue', 'assign');
                } else {
                    $cell2content = format_time($duedate - $time);
                }
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if ($duedate < $time) {
                $cell1content = get_string('latesubmissions', 'assign');
                $cutoffdate = $summary->cutoffdate;
                if ($cutoffdate) {
                    if ($cutoffdate > $time) {
                        $cell2content = get_string('latesubmissionsaccepted', 'assign', userdate($summary->cutoffdate));
                    } else {
                        $cell2content = get_string('nomoresubmissionsaccepted', 'assign');
                    }

                    $this->add_table_row_tuple($t, $cell1content, $cell2content);
                }
            }

        }

        // All done - write the table.
        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Link to the grading page.
        $o .= html_writer::start_tag('center');
        $o .= $this->output->container_start('submissionlinks');
        $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grading');
        $url = new moodle_url('/mod/workflow/view.php', $urlparams);
        $o .= html_writer::link($url, 'View all requests',
            ['class' => 'btn btn-secondary']);
        if ($summary->cangrade) {
            $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grader');
            $url = new moodle_url('/mod/workflow/view.php', $urlparams);
            $o .= html_writer::link($url, 'Approve',
                ['class' => 'btn btn-primary ml-1']);
        }
        $o .= $this->output->container_end();

        // Close the container and insert a spacer.
        $o .= $this->output->container_end();
        $o .= html_writer::end_tag('center');

        return $o;
    }

    /**
     * Render a table containing the current status of the grading process.
     *
     * @param assign_grading_summary $summary
     * @return string
     */
    public function render_workflow_request_status(workflow_request_status $status)
    {

        //TODO : check dates with time() & determine the variables and give buttons
        /* $canedit,
                                 $cansubmit,
                                 $canremove,*/
        //create table for data
        $o = '';
        $o .= $this->output->container_start('requestsummary');
        $o .= $this->output->heading('Request summary');
        $o .= $this->output->box_start('boxaligncenter requestsummarytable');
        $t = new html_table();

        //submission status
        $cell1content = get_string('submissionstatus', 'workflow');
        $cell2content = ($status->submission === null) ? get_string('noattempt', 'workflow') : get_string('submittedforgrading', 'workflow');
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        //request approval status
        $cell1content = get_string('approvalstatus', 'workflow');
        if ($status->submission === null) $cell2content = get_string('noattempt', 'workflow');
        else {
            $cell2content = $status->request_status;
        }
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Due date.
        $cell1content = get_string('duedate', 'workflow');
        $duedate = $status->duedate;
        $cell2content = userdate($duedate);
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        //time remaining
        $time = time();
        $cell1content = get_string('timeremaining', 'workflow');
        if ($duedate - $time <= 0) {
            $cell2content = get_string('assignmentisdue', 'assign');
        } else {
            $cell2content = format_time($duedate - $time);
        }
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        //Last submission made
        $cell1content = get_string('lastmodified', 'workflow');
        if($status->submission == null) $cell2content = '-';
        else{$cell2content = userdate($status->submitteddate);}
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        //only if the student have a previous submission
        if($status->submission !== null){
            $submission = $status->submission;
            $cell1content = get_string('reason', 'workflow');
            $cell2content = $submission->reason;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            $cell1content = get_string('comments', 'workflow');
            $cell2content = ($submission->comments === null) ? '-' : $submission->comments;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            $cell1content = get_string('filesubmission', 'workflow');
            //TODO :file submission
            $cell2content = '-';
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        // Link to the add request page.
        $o .= html_writer::start_tag('center');
        $o .= $this->output->container_start('submissionlinks');

        $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');

        if($status->submission === null){
            $url = new moodle_url('/mod/workflow/view.php', $urlparams);
            $o .= html_writer::link($url, 'Add submission',
                ['class' => 'btn btn-secondary']);
        }
        else{
            $urlparams = array('id' => $status->coursemoduleid, 'action' => 'removesubmission');
            $url = new moodle_url('/mod/workflow/view.php', $urlparams);
            $o .= html_writer::link($url, 'Remove submission',
                ['class' => 'btn btn-secondary']);
        }
        $o .= $this->output->container_end();

        // Close the container and insert a spacer.
        $o .= $this->output->container_end();
        $o .= html_writer::end_tag('center');


            /*$o .= html_writer::start_tag('center');
            $o .= $this->output->container_start('submissionlinks');*/
        return $o;

        }

    /**
     * Helper method dealing with the fact we can not just fetch the output of moodleforms
     *
     * @param moodleform $mform
     * @return string HTML
     */
    protected function moodleform(moodleform $mform) {

        $o = '';
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Render the generic form
     * @param workflow_requestform $form The form to render
     * @return string
     */
    public function render_workflow_requestform(workflow_requestform $form) {
        $o = '';
        if ($form->jsinitfunction) {
            $this->page->requires->js_init_call($form->jsinitfunction, array());
        }
        $o .= $this->output->box_start('boxaligncenter ' . $form->classname);
        $o .= $this->moodleform($form->form);
        $o .= $this->output->box_end();
        return $o;
    }

    /**
     * Render the generic form
     * @param workflow_requestapprove $form The form to render
     * @return string
     */
    public function render_workflow_requestapprove(workflow_requestapprove $form) {
        $o = '';
        if ($form->jsinitfunction) {
            $this->page->requires->js_init_call($form->jsinitfunction, array());
        }
        $o .= $this->output->box_start('boxaligncenter ' . $form->classname);
        $o .= $this->moodleform($form->form);
        $o .= $this->output->box_end();
        return $o;
    }
}