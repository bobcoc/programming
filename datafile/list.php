<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $params = array('id' => $id);
    $PAGE->set_url('/mod/programming/datafile/list.php', $params);

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
    $tabs = programming_navtab('edittest', 'datafile', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print page content
    echo html_writer::tag('h2', $programming->name);
    echo html_writer::tag('h3', get_string('datafiles', 'programming').$OUTPUT->help_icon('datafile', 'programming'));
    print_datafile_table();

/// Finish the page
    echo $OUTPUT->footer($course);

function print_datafile_table() {
    global $CFG, $DB, $OUTPUT, $cm, $params, $page, $perpage, $programming, $course, $language, $groupid;

    $table = new html_table();
    $table->head = array(
        get_string('sequence', 'programming'),
        get_string('filename', 'programming'),
        get_string('filetype', 'programming'),
        get_string('data', 'programming'),
        get_string('checkdata', 'programming'),
        get_string('action'),
        );
    $table->data = array();

    /**$table->set_attribute('id', 'presetcode-table');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('align', 'center');
    $table->set_attribute('cellpadding', '3');
    $table->set_attribute('cellspacing', '1');
    $table->no_sorting('code');*/

    $strpresstodownload = get_string('presstodownload', 'programming');
    $strbinaryfile = get_string('binaryfile', 'programming');
    $strtextfile = get_string('textfile', 'programming');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    $files = $DB->get_records('programming_datafile', array('programmingid' => $programming->id), 'seq');
    if (is_array($files)) {
        $files_count = count($files)-1;
        $i = 0;
        foreach ($files as $file) {
            $data = array();
            $data[] = $file->seq;
            $data[] = htmlentities($file->filename);
            $data[] = $file->isbinary ? $strbinaryfile : $strtextfile;
            $size = programming_format_codesize($file->datasize);
            $url = new moodle_url('/mod/programming/datafile/download.php', array('id' => $cm->id, 'datafile' => $file->id));
            $data[] = $OUTPUT->action_link($url, $size, null, array('title' => $strpresstodownload));
            if ($file->checkdatasize) {
                $size = programming_format_codesize($file->checkdatasize);
                $url->param('checkdata', 1);
                $data[] = $OUTPUT->action_link($url, $size, null, array('title' => $strpresstodownload));
            } else {
                $data[] = get_string('n/a', 'programming');
            }
            $url = new moodle_url('/mod/programming/datafile/edit.php', array('id' => $cm->id, 'datafile' => $file->id));
            $html = $OUTPUT->action_link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'))), null, array('class' => 'icon edit', 'title' => $stredit));
            $url = new moodle_url('/mod/programming/datafile/delete.php', array('id' => $cm->id, 'datafile' => $file->id));
            $act = new confirm_action(get_string('deletedatafileconfirm', 'programming'));
            $html .= $OUTPUT->action_link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'))), $act, array('class' => 'icon delete', 'title' => $strdelete));
            if ($i > 0) {
                $url = new moodle_url('/mod/programming/datafile/move.php', array('id' => $cm->id, 'datafile' => $file->id, 'direction' => 1));
                $html .= $OUTPUT->action_link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/up'))), null, array('class' => 'icon up', 'title' => $strmoveup));
            }
            if ($i < $files_count) {
                $url = new moodle_url('/mod/programming/datafile/move.php', array('id' => $cm->id, 'datafile' => $file->id, 'direction' => 2));
                $html .= $OUTPUT->action_link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/down'))), null, array('class' => 'icon down', 'title' => $strmovedown));
            }
            $data[] = $html;
            $table->data[] = $data;
            $i++;
        }

        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('nodatafile', 'programming'));
    }
    echo html_writer::tag('p', $OUTPUT->action_link(new moodle_url('/mod/programming/datafile/add.php', array('id' => $cm->id)), get_string('adddatafile', 'programming')));
}

?>
