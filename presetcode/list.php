<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $params = array('id' => $id);
    $PAGE->set_url('/mod/programming/presetcode/list.php', $params);

    if (! $cm = get_coursemodule_from_id('programming', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
        print_error('invalidprogrammingid', 'programming');
    }

    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);

    require_capability('mod/programming:viewhiddentestcase', $context);

/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('edittest', 'presetcode', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print page content
    echo html_writer::tag('h2', $programming->name);
    echo html_writer::tag('h3', get_string('presetcode', 'programming').$OUTPUT->help_icon('presetcode', 'programming'));
    print_presetcode_table();

/// Finish the page
    echo $OUTPUT->footer($course);

function print_presetcode_table() {
    global $CFG, $DB, $OUTPUT, $cm, $page, $perpage, $programming, $course, $language, $groupid;

    $table = new html_table();
    $table->head = array(
        get_string('sequence', 'programming'),
        get_string('name', 'programming'),
        get_string('language'),
        get_string('codeforuser', 'programming'),
        get_string('codeforcheck', 'programming'),
        get_string('action'),
        );
    $table->data = array();

    /*$table->set_attribute('id', 'presetcode-table');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('align', 'center');
    $table->set_attribute('cellpadding', '3');
    $table->set_attribute('cellspacing', '1');
    $table->no_sorting('code');
    $table->setup();*/

    $codes = $DB->get_records('programming_presetcode', array('programmingid' => $programming->id), 'sequence');
    if (is_array($codes)) {
        $langs = $DB->get_records('programming_languages');
        $codes_count = count($codes)-1;
        $i = 0;

        $stredit = get_string('edit');
        $strdelete = get_string('delete');
        $strmoveup = get_string('moveup');
        $strmovedown = get_string('movedown');

        foreach ($codes as $code) {
            $data = array();
            $data[] = $code->sequence;
            $data[] = htmlentities($code->name);
            $data[] = $langs[$code->languageid]->name;
            $data[] = $code->presetcode ? 'Yes' : '';
            $data[] = $code->presetcodeforcheck ? 'Yes' : '';
            $url = new moodle_url('/mod/programming/presetcode/edit.php', array('id' => $cm->id, 'code' => $code->id));
            $html = $OUTPUT->action_link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'))), null, array('class' => 'icon edit', 'title' => $stredit));
            $url = new moodle_url('/mod/programming/presetcode/delete.php', array('id' => $cm->id, 'code' => $code->id));
            $act = new confirm_action(get_string('presetcodedeleted', 'programming'));
            $html .= $OUTPUT->action_link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'))), $act, array('class' => 'icon delete', 'title' => $strdelete));
            if ($i > 0) {
                $url = new moodle_url('/mod/programming/presetcode/move.php', array('id' => $cm->id, 'code' => $code->id, 'direction' => 1));
                $html .= $OUTPUT->action_link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/up'))), null, array('class' => 'icon up', 'title' => $strmoveup));
            }
            if ($i < $codes_count) {
                $url = new moodle_url('/mod/programming/presetcode/move.php', array('id' => $cm->id, 'code' => $code->id, 'direction' => 2));
                $html .= $OUTPUT->action_link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/down'))), null, array('class' => 'icon down', 'title' => $strmovedown));
            }
            $data[] = $html;
            $table->data[] = $data;
            $i++;
        }

        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('nopresetcode', 'programming'));
    }
    echo html_writer::tag('p', $OUTPUT->action_link(new moodle_url('/mod/programming/presetcode/add.php', array('id' => $cm->id)), get_string('addpresetcode', 'programming')));

}

?>
