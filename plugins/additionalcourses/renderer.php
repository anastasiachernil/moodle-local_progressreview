<?php
class progressreview_additionalcourses_renderer extends plugin_renderer_base {
    public function review($additionalcourses) {
        if ($additionalcourses) {
            $courses = array();
            $output = $this->output->heading(get_string('pluginname', 'progressreview_additionalcourses'), 2);
            foreach ($additionalcourses as $additionalcourse) {
                $courses[] = $additionalcourse;
            }
            $output .= html_writer::alist($courses);
            return $output;
        }
    }
}

class progressreview_additionalcourses_print_renderer extends plugin_print_renderer_base {
    public function review($additionalcourses) {
        $additionalcourses = array_filter($additionalcourses);
        if (!empty($additionalcourses)) {
            $courses = array();
            $options = array('font' => (object)array('size' => 12));
            $this->output->heading(get_string('pluginname', 'progressreview_additionalcourses'), 4);
            foreach ($additionalcourses as $additionalcourse) {
                pdf_writer::div($additionalcourse, $options);
            }
            return pdf_writer::$pdf;
        }
    }
}