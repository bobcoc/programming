<?php

class mod_programming_renderer extends plugin_renderer_base {

    function render_filters($filters, $url, $defaults) {
        $output = '';

        $output .= html_writer::start_tag('div', array('class' => 'filters'));
        foreach ($filters as $param => $filter) {
            $output .= html_writer::start_tag('dl', array('class' => $param));
            $output .= html_writer::tag('dt', $filter['title']);
            $output .= html_writer::start_tag('dd');
            foreach ($filter['options'] as $key => $value) {
                $nurl = new moodle_url($url);
                $nurl->param($param, $key);
                $attrs = array('href' => $nurl, 'title' => $value);
                if ($defaults[$param] == $key) {
                    $attrs['class'] = 'here';
                }

                $output .= html_writer::tag('span', html_writer::tag('a', $value, $attrs));
            }
            $output .= html_writer::end_tag('dd');
            $output .= html_writer::end_tag('dl');
        }
        $output .= html_writer::end_tag('div');

        return $output;
    }

    function render_navtab($tab) {
        return print_tabs($tab->tabs, $tab->currenttab, $tab->inactive, $tab->active, true);
    }

}

