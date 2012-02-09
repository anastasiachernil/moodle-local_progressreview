<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Defines the plugin's renderer to generate all HTML
 *
 * @package   local_progressreview
 * @copyright 2011 Taunton's College, UK
 * @author    Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_progressreview_renderer extends plugin_renderer_base {

    function editicon($url) {
        $icon = $this->output->pix_icon('t/edit', get_string('edit'));
        $link = html_writer::link($url, $icon);
        return $link;
    }

    function sessions_table($sessions) {
        $table = new html_table();
        $table->head = array(
            get_string('name', 'local_progressreview'),
            get_string('deadline_subject', 'local_progressreview'),
            get_string('deadline_tutor', 'local_progressreview'),
            ''
        );

        foreach ($sessions as $session) {
            $params = array('id' => $session->id);
            $sessionurl = new moodle_url('/local/progressreview/session.php', $params);
            $params = array('editid' => $session->id);
            $editurl = new moodle_url('/local/progressreview/session.php', $params);

            $subject_deadline = $session->deadline_subject ? date('D d/m/Y', $session->deadline_subject) : '';
            $tutor_deadline = $session->deadline_tutor ? date('D d/m/Y', $session->deadline_tutor) : '';
            $row = new html_table_row(array(
                html_writer::link($sessionurl, $session->name),
                $subject_deadline,
                $tutor_deadline,
                $this->editicon($editurl)
            ));
            if ($session->deadline_subject > time() || $session->deadline_tutor > time()) {
                $row->attributes['class'] = 'current';
            }
            $table->data[] = $row;
        }

        $output = $this->output->heading(get_string('sessions', 'local_progressreview'), 2);
        $output .= html_writer::table($table);
        $label = get_string('createsession', 'local_progressreview');
        $output .= $this->output->single_button('/local/progressreview/session.php', $label);

        return $output;
    }

    function department_table($department, $subjectsummaries, $tutorsummaries) {
        $strheading = get_string('reviewsfordept', 'local_progressreview', $department->name);
        $output = $this->output->heading($strheading, 2);

        $types = array('subjectreviews' => $subjectsummaries, 'tutorreviews' => $tutorsummaries);
        foreach ($types as $type => $summaries) {

            if ($summaries) {
                $table = new html_table();
                $table->id = $type;
                $table->head = array(
                    get_string('name', 'local_progressreview'),
                    get_string('teachers', 'local_progressreview'),
                    get_string('reviews', 'local_progressreview'),
                    get_string('completedreviews', 'local_progressreview'),
                    get_string('outstandingreviews', 'local_progressreview'),
                    ''
                );

                foreach ($summaries as $summary) {
                    $courseattrs = array(
                        'courseid' => $summary->courseid,
                        'teacherid' => $summary->teacherid
                    );
                    $courseurl = new moodle_url('/local/progressreview/index.php', $courseattrs);
                    $deleteicon = $this->output->pix_icon('t/delete', get_string('delete'));
                    $deleteattrs = $courseattrs;
                    $deleteattrs['sessionid'] = $summary->sessionid;
                    $deleteurl = new moodle_url('/local/progressreview/delete.php', $deleteattrs);
                    $attrs = array('class' => 'delete');
                    $deletelink = html_writer::link($deleteurl, $deleteicon, $attrs);

                    $row = new html_table_row(array(
                        html_writer::link($courseurl, $summary->name),
                        $summary->teacher,
                        $summary->total,
                        $summary->completed,
                        ($summary->total)-($summary->completed),
                        $deletelink
                    ));

                    $row->attributes['class'] = 'incomplete';
                    if ($summary->completed == 0) {
                        $row->attributes['class'] = 'empty';
                    }
                    if ($summary->completed == $summary->total) {
                        $row->attributes['class'] = 'completed';
                    }

                    $table->data[] = $row;
                }

                $output .= $this->output->heading(get_string($type, 'local_progressreview'), 3);
                $output .= html_writer::table($table);

                $jsmodule = array(
                    'name' => 'local_progressreview',
                    'fullpath' => '/local/progressreview/module.js',
                    'requires' => array(
                        'base',
                        'node',
                        'event-delegate',
                        'yui2-event',
                        'yui2-container',
                        'yui2-button'
                    ),
                    'strings' => array(
                        array('confirmdelete', 'local_progressreview'),
                        array('continue', 'moodle'),
                        array('cancel', 'moodle')
                    )
                );
                $this->page->requires->js_init_call('M.local_progressreview.init_delete',
                                                    array(sesskey(), $summary->sessionname),
                                                    false,
                                                    $jsmodule);

                $this->page->requires->js_init_call('M.local_progressreview.init_filters',
                                                    null,
                                                    false,
                                                    $jsmodule);
            }
        }

        return $output;
    }

    function filter_fields() {
        $output = '';
        $strdept = get_string('filterdept', 'local_progressreview');
        $strcourse = get_string('filtercourse', 'local_progressreview');
        $strteacher = get_string('filterteacher', 'local_progressreview');
        //$output .= html_writer::label($strdept, 'filterdept');
        //$attrs = array('id' => 'filterdept', 'name' => 'filterdept');
        //$output .= html_writer::empty_tag('input', $attrs);
        $output .= html_writer::label($strcourse, 'filtercourse');
        $attrs = array('id' => 'filtercourse', 'name' => 'filtercourse');
        $output .= html_writer::empty_tag('input', $attrs);
        $output .= html_writer::label($strteacher, 'filterteacher');
        $attrs = array('id' => 'filterteacher', 'name' => 'filterteacher');
        $output .= html_writer::empty_tag('input', $attrs);
        return $this->output->container($output, '', 'filterfields');
    }

    function course_table($course) {
        return 'Whoops! not done yet!';
    }

    function courses_table($courses) {
        $strheading = get_string('courseswithreviews', 'local_progressreview');
        $output = $this->output->heading($strheading, 2);

        $table = new html_table();
        $table->head = array(
            get_string('reference', 'local_progressreview'),
            get_string('name', 'local_progressreview')
        );

        foreach ($courses as $course) {
            $params = array('courseid' => $course->id);
            $url = new moodle_url('/local/progressreview/review.php', $params);
            $row = new html_table_row(array(
                html_writer::link($url, $course->shortname),
                html_writer::link($url, $course->fullname)
            ));
            $table->data[] = $row;
        }

        $output .= html_writer::table($table);
        return $output;
    }

    function session_links($url, $sessions) {
        $sessionlinks = array();
        foreach($sessions as $session) {
            $url = new moodle_url($url, array('sessionid' => $session->id));
            $sessionlinks[] = html_writer::link($url, $session->name);
        }
        return html_writer::alist($sessionlinks);
    }

    function course_selector_form($potential_selector,
                                  $distributed_selector,
                                  $sessionid,
                                  $type = PROGRESSREVIEW_SUBJECT) {

        $buttonsuffix = ($type == PROGRESSREVIEW_SUBJECT) ? '_subject' : '_tutor';
        $output = '';
        $table = new html_table('course_selector');
        $row = new html_table_row();
        $row->cells[] = $distributed_selector->display(true);
        $strcreatereviews = get_string('createreviews', 'local_progressreview');
        $attrs = array(
            'class' => 'course_selector_button',
            'name' => 'generate'.$buttonsuffix,
            'type' => 'submit',
            'value' => $this->output->larrow().' '.$strcreatereviews
        );
        $cell = html_writer::empty_tag('input', $attrs);
        $row->cells[] = $cell;
        $row->cells[] = $potential_selector->display(true);
        $table->data[] = $row;

        $attrs = array('action' => $this->page->url->out(), 'method' => 'post');
        $output = html_writer::start_tag('form', $attrs);
        $attrs = array('type' => 'hidden', 'name' => 'id', 'value' => $sessionid);
        $output .= html_writer::empty_tag('input', $attrs);
        $output .= html_writer::table($table);
        $attrs = array(
            'type' => 'submit',
            'name' => 'regenerate'.$buttonsuffix,
            'value' => get_string('regenerate', 'local_progressreview')
        );
        $output .= html_writer::empty_tag('input', $attrs);
        if ($type == PROGRESSREVIEW_SUBJECT) {
            $attrs = array(
                'type' => 'submit',
                'name' => 'snapshot',
                'value' => get_string('snapshot', 'local_progressreview')
            );
            $output .= html_writer::empty_tag('input', $attrs);
        }
        $output .= html_writer::end_tag('form');

        return $output;

    }

    function changescale_button($sessionid, $courseid) {
        $params = array('sessionid' => $sessionid, 'courseid' => $courseid);
        $url = new moodle_url('/local/progressreview/changescale.php', $params);
        $strchangescale = get_string('changescale', 'local_progressreview');
        $button = $this->output->single_button($url, $strchangescale, 'get');
        $strsavefirst = get_string('savefirst', 'local_progressreview');
        return $this->output->container($button.$strsavefirst, array('changescale'));
    }

    public function subject_review_table($reviews,
                                         $form = true,
                                         $displayby = PROGRESSREVIEW_STUDENT) {

        $output = '';
        $table = new html_table();
        $table->head = array(
            '',
            '',
            get_string('attendance', 'local_progressreview'),
            get_string('punctuality', 'local_progressreview'),
            get_string('homework', 'local_progressreview'),
            get_string('behaviour', 'local_progressreview'),
            get_string('effort', 'local_progressreview'),
        );
        if ($form) {
            $table->head[] = get_string('minimumgrade', 'local_progressreview');
        }
        $table->head[] = get_string('targetgrade', 'local_progressreview');
        $table->head[] = get_string('performancegrade', 'local_progressreview');

        if (!$form && $displayby == PROGRESSREVIEW_TEACHER) {
            $table->head[0] = get_string('course');
            $table->head[1] = get_string('teacher', 'local_progressreview');
        }

        $rows = array();
        foreach ($reviews as $review) {
            $student = $review->get_student();
            $session = $review->get_session();
            $plugins = $review->get_plugins();
            if ($form) {

                $idattrs = array(
                    'type' => 'hidden',
                    'id' => 'id_student_'.$review->id,
                    'value' => $student->id
                );

                $output .= html_writer::empty_tag('input', $idattrs);
                foreach ($plugins as $pluginnname => $plugin) {
                    $newrows = $plugin->add_form_rows();
                    foreach ($newrows as $newrow) {
                        if (get_class($newrow) != 'html_table_row') {
                            throw new coding_exception('add_form_rows must return an
                                array of html_table_row objects. The '.$pluginname.' plugin
                                didn\'t do this.');
                        }
                    }
                    $rows = array_merge($rows, $newrows);
                }

            } else {
                foreach ($plugins as $pluginname => $plugin) {
                    $newrows = $plugin->add_table_rows($displayby == PROGRESSREVIEW_STUDENT);
                    foreach ($newrows as $newrow) {
                        if (get_class($newrow) != 'html_table_row') {
                            throw new coding_exception('add_table_rows must return an
                                array of html_table_row objects. The '.$pluginname.' plugin
                                didn\'t do this.');
                        }
                    }
                    $rows = array_merge($rows, $newrows);
                }
            }
        }

        $table->data = $rows;
        $params = array('action' => $this->page->url->out_omit_querystring(), 'method' => 'post');
        $output .= html_writer::start_tag('form', $params);
        $output .= html_writer::input_hidden_params($this->page->url);
        $output .= html_writer::table($table);

        if ($form) {
            $hiddens = array(
                'sessionid' => $review->get_session()->id,
                'courseid' => $review->get_course()->originalid,
                'teacherid' => $review->get_teacher()->originalid,
                'reviewtype' => $review->get_type(),
                'editid' => ''
            );
            $hiddenparams = array(
                'type' => 'hidden',
            );
            foreach ($hiddens as $name => $value) {
                $hiddenparams['name'] = $name;
                $hiddenparams['id'] = 'id_'.$name;
                $hiddenparams['value'] = $value;
                $output .= html_writer::empty_tag('input', $hiddenparams);
            }

            $strsave = get_string('savechanges');
            $output .= html_writer::empty_tag('input', array(
                'id' => 'id_save',
                'name' => 'submit',
                'type' => 'submit',
                'value' => $strsave
            ));

            $jsmodule = array(
                'name' => 'local_progressreview',
                'fullpath' => '/local/progressreview/module.js',
                'requires' => array(
                    'base',
                    'node',
                    'io',
                    'json',
                    'event-valuechange',
                    'event-delegate'
                ),
                'strings' => array(
                    array('autosaveactive', 'local_progressreview'),
                    array('autosavefailed', 'local_progressreview'),
                    array('autosaving', 'local_progressreview')
                )
            );

            $this->page->requires->js_init_call('M.local_progressreview.init_autosave',
                                                array($strsave),
                                                false,
                                                $jsmodule);

            foreach ($review->get_plugins() as $plugin) {
                $modulename = 'M.progressreview_'.$plugin->get_name();
                $this->page->requires->js_init_call($modulename.'.init_autosave');
            }
            $output .= $this->progress_indicator();

        }
        return $output;

    }

    public function tutor_review_table($review) {
        $output = '';
        $plugins = $review->get_plugins();
        foreach ($plugins as $plugin) {
            $output .= $plugin->get_static_output();
        }
    }

    public function tutorgroup_list($progressreviews) {
        $table = new html_table();
        $strstudent = get_string('student', 'local_progressreview');
        $strcomments = get_string('commentswritten', 'local_progressreview');
        $table->head = array($strstudent, $strcomments);
        foreach($progressreviews as $progressreview) {

            $review = $progressreview->get_plugin('tutor')->get_review();
            $student = $progressreview->get_student();
            $name = fullname($student);
            if(empty($review->comments)) {
                $completed = get_string('no');
            } else {
                $completed = get_string('yes');
            }

            $params = array(
                'courseid' => $progressreview->get_course()->originalid,
                'studentid' => $student->id,
                'sessionid' => $progressreview->get_session()->id
            );
            $reviewurl = new moodle_url('/local/progressreview/tutorreview.php', $params);
            $link = html_writer::link($reviewurl, $name);
            $row = array($link, $completed);
            $table->data[] = $row;
        }
        return html_writer::table($table);
    }

    public function user_session_links($user, $sessions, $currentid) {
        $links = array();
        foreach ($sessions as $session) {
            $params = array('sessionid' => $session->id, 'userid' => $user->id);
            $url = new moodle_url('/local/progressreview/user.php', $params);
            $link = html_writer::link($url, $session->name);
            if ($session->id == $currentid) {
                $link = html_writer::tag('strong', $link);
            }
            $links[] = $link;
        }
        return html_writer::alist($links);
    }


    public function progress_indicator() {
        $loader = $this->output->pix_icon('i/loading_small', '');
        $strautosave = get_string('autosaveactive', 'local_progressreview');
        $label = html_writer::tag('span', $strautosave, array('id' => 'autosavelabel'));

        return $this->output->container($loader.$label, '', 'progressindicator');
    }

    public function tabs($active) {
        $tabs = array();
        $tabs[] = new tabobject(1,
                new moodle_url('/local/progressreview/index.php'),
                get_string('manage', 'local_progressreview'));
        $tabs[] = new tabobject(2,
                new moodle_url('/local/progressreview/print.php'),
                get_string('print', 'local_progressreview'));
        print_tabs(array($tabs), $active);
    }

    public function print_selectors($session, $student, $course, $teacher) {
        $hw = 'html_writer';
        $fields = '';
        $rows = array(
            'session' => array(
                'label' => $hw::label(get_string('sessions', 'local_progressreview'), 'sessionselect'),
                'selector' => $session
            ),
            'student' => array(
                'label' => $hw::label(get_string('students', 'local_progressreview'), 'studentselect'),
                'selector' => $student
            ),
            'course' => array(
                'label' => $hw::label(get_string('courses', 'local_progressreview'), 'courseselect'),
                'selector' => $course
            ),
            'teacher' => array(
                'label' => $hw::label(get_string('teachers', 'local_progressreview'), 'teacherselect'),
                'selector' => $teacher
            )
        );

        foreach ($rows as $row) {
            $label = $this->output->container($row['label'], 'fitemtitle');
            $field = $this->output->container($row['selector']->display(true), 'felement');
            $fields .= $this->output->container($label.$field, 'fitem');
        }

        $sesskeyattrs = array(
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey()
        );
        $fields .= $hw::empty_tag('input', $sesskeyattrs);

        $submitattrs = array(
            'value' => get_string('continue'),
            'type' => 'submit',
            'name' => 'continue'
        );

        $legend = $hw::tag('legend', get_string('selectcriteria', 'local_progressreview'));
        $fieldset = $hw::tag('fieldset', $legend.$fields);

        $submit = $hw::empty_tag('input', $submitattrs);
        $formattrs = array(
            'method' => 'post',
            'action' => $this->page->url->out(),
            'class' => 'mform'
        );
        $form = $hw::tag('form', $fieldset.$submit, $formattrs);
        return $form;

    }

    public function print_confirmation($sessions, $students, $courses, $teachers) {
        $sessionnames = array();
        $studentnames = array();
        $coursenames = array();
        $teachernames = array();
        foreach ($sessions as $session) {
            $sessionnames[] = fullname($session);
        }
        foreach ($students as $student) {
            $studentnames[] = fullname($student);
        }
        foreach ($courses as $course) {
            $coursenames[] = fullname($course);
        }
        foreach ($teachers as $teacher) {
            $teachernames[] = fullname($teacher);
        }
        $table = new html_table;
        $table->head = array(
            get_string('sessions', 'local_progressreview'),
            get_string('students', 'local_progressreview'),
            get_string('courses', 'local_progressreview'),
            get_string('teachers', 'local_progressreview')
        );
        $strall = get_string('all', 'local_progressreview');
        $table->data[] = new html_table_row(array(
            empty($sessionnames) ? $strall : html_writer::alist($sessionnames),
            empty($studentnames) ? $strall : html_writer::alist($studentnames),
            empty($coursenames) ? $strall : html_writer::alist($coursenames),
            empty($teachernames) ? $strall : html_writer::alist($teachernames)
        ));

        $buttons = '';
        $url = '/local/progressreview/print.php';
        $backurl = new moodle_url($url);
        $confirmparams = array(
            'sessions' => json_encode(array_keys($sessions)),
            'students' => json_encode(array_keys($students)),
            'courses' => json_encode(array_keys($courses)),
            'teachers' => json_encode(array_keys($teachers)),
            'generate' => true
        );
        $viewurl = new moodle_url($url, $confirmparams);
        $confirmparams['download'] = true;
        $downloadurl = new moodle_url($url, $confirmparams);
        $buttons .= $this->output->single_button($backurl, 'Back');
        $buttons .= $this->output->single_button($viewurl,
                                                 get_string('generateandview', 'local_progressreview'),
                                                 'post');
        $buttons .= $this->output->single_button($downloadurl,
                                                 get_string('generateanddownload', 'local_progressreview'),
                                                 'post');

        $output = html_writer::table($table).$buttons;
        return $output;

    }

    public function disable_memlimit_button() {
        $url = new moodle_url('/local/progressreview/print.php', array('disablememlimit' => true));
        $label = get_string('disablememlimit', 'local_progressreview');
        return $this->output->single_button($url, $label, 'post');
    }

}

class local_progressreview_print_renderer extends plugin_renderer_base {

    public function subject_review_table($reviews, $form = false, $previousreviews = array()) {
        $form = false;
        $table = new html_table();

        $table->head = array(
            get_string('course'),
            get_string('teacher', 'local_progressreview'),
            get_string('attendance', 'local_progressreview'),
            get_string('punctuality', 'local_progressreview'),
            get_string('homework', 'local_progressreview'),
            get_string('behaviour', 'local_progressreview'),
            get_string('effort', 'local_progressreview'),
            get_string('targetgrade', 'local_progressreview'),
            get_string('performancegrade', 'local_progressreview')
        );

        foreach ($reviews as $key => $review) {
            $student = $review->progressreview->get_student();
            $session = $review->progressreview->get_session();
            $coursename = $review->progressreview->get_course()->fullname;
            $name = fullname($review->progressreview->get_teacher());
            $attendance = number_format($review->attendance, 0).'%';
            $punctuality = number_format($review->punctuality, 0).'%';
            $fieldarray = 'review['.$review->id.']';
            $homework = $review->homeworkdone.'/'.$review->homeworktotal;
            $behaviour = @$session->scale_behaviour[$review->behaviour];
            $effort = @$session->scale_effort[$review->effort];
            $targetgrade = @$review->scale[$review->targetgrade];
            $performancegrade = @$review->scale[$review->performancegrade];
            $previousexists = array_key_exists($key, $previousdata);
            if ($session->previoussession && $previousexists && !empty($previousdata[$key])) {
                $p = $previousdata[$key];
                if (!isset($psession)) {
                    $psession = $p->progressreview->get_session();
                }
                $attendance .= $this->previous_data(number_format($p->attendance, 0).'%');
                $punctuality .= $this->previous_data(number_format($p->punctuality, 0).'%');
                $homework .= $this->previous_data($p->homeworkdone.'/'.$p->homeworktotal);
                $behaviour .= $this->previous_data(@$psession->scale_behaviour[$p->behaviour]);
                $effort .= $this->previous_data(@$psession->scale_effort[$p->effort]);
                $targetgrade .= $this->previous_data(@$p->scale[$p->targetgrade]);
                $performancegrade .= $this->previous_data(@$p->scale[$p->performancegrade]);
            }

            if (!empty($behaviour) || !empty($effort) || !empty($targetgrade) || !empty($performancegrade) || !empty($review->comments)) {
                $row = array(
                    $coursename,
                    $name,
                    $attendance,
                    $punctuality,
                    $homework,
                    $behaviour,
                    $effort,
                    $targetgrade,
                    $performancegrade
                );

                $table->data[] = $row;

                if (!$session->inductionreview) {
                    $strcomments = get_string('commentstargets', 'local_progressreview');
                    $headercell = new html_table_cell($strcomments.':');
                    $headercell->header = true;

                    $commentscell = new html_table_cell($review->comments);
                    $commentscell->colspan = 7;
                    $row = new html_table_row(array('', $headercell, $commentscell));
                    $table->data[] = $row;
                }
            }
        }

        $table->size = array(70, 70, 70, 70, 70, 70, 70, 70, 70);
        pdf_writer::change_font((object)array('size' => 8));

        pdf_writer::table($table);
        pdf_writer::$pdf->Ln(30);

    }

    public static function heading($text, $level = 1, $options = array()) {
        $sizes = array(null, 18, 16, 14, 12, 10);
        $size = $sizes[$level];
        if (!array_key_exists('font', $options)) {
            $options['font'] = new stdClass;
        }
        $options['font']->size = $size;
        $options['font']->decoration = 'B';

        return pdf_writer::div($text, $options);
    }
}

class plugin_print_renderer_base extends plugin_renderer_base {

    public function __construct(moodle_page $page, $target) {
        global $output;
        parent::__construct($page, $target);
        $this->output = $output;
    }
}

