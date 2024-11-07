<?php

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);    // Course Module ID, or
    $groupid = optional_param('group', 0, PARAM_INT);
    $page = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 10, PARAM_INT);
    $latestonly = optional_param('latestonly', 1, PARAM_INT);
    $judgeresult = optional_param('judgeresult', '', PARAM_CLEAN);
    $firstinitial = optional_param('firstinitial', '', PARAM_CLEAN);
    $lastinitial = optional_param('lastinitial', '', PARAM_CLEAN);

    if (! $cm = get_coursemodule_from_id('programming', $id)) {
        print_error('Course Module ID was incorrect');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('Course is misconfigured');
    }

    if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
        print_error('Course module is incorrect');
    }

    $params = array('id' => $cm->id,
                    'latestonly' => $latestonly,
                    'lastinitial' => $lastinitial,
                    'firstinitial' => $firstinitial,
                    'group' => $groupid,
                    'judgeresult' => $judgeresult,
                    'page' => $page,
                    'perpage' => $perpage);

    $PAGE->set_url('/mod/programming/reports/detail.php', $params);

    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);

    require_capability('mod/programming:viewreport', $context);

    $rejudge = has_capability('mod/programming:rejudge', $context);
    $deleteothersubmit = has_capability('mod/programming:deleteothersubmit', $context);
    $viewotherresult = has_capability('mod/programming:viewotherresult', $context);
    $viewotherprogram = has_capability('mod/programming:viewotherprogram', $context);


    list($submits, $totalcount) = get_submits();

/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('reports', 'reports-detail', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print the main part of the page
    echo html_writer::tag('h2', get_string('allprograms', 'programming'));

    $renderer = $PAGE->get_renderer('mod_programming');
    echo $renderer->render_filters(build_filters(), $PAGE->url, $params);

    if (is_array($submits)) {
        $table = build_result_table($submits, $totalcount);
        $strrejudge = get_string('rejudge', 'programming');
        $strdelete = get_string('delete');
        echo html_writer::start_tag('form', array('id' => 'submitaction', 'method' => 'post'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $cm->id));
        echo html_writer::table($table);        
        $pagingbar = new paging_bar($totalcount, $page, $perpage, $PAGE->url, 'page');
        echo $OUTPUT->render($pagingbar);
        echo html_writer::start_tag('div', array('id' => 'submitbuttons', 'style' => 'display: none'));
        echo html_writer::empty_tag('input', array('id' => 'rejudge', 'type' => 'button', 'value' => $strrejudge));
        echo html_writer::empty_tag('input', array('id' => 'delete', 'type' => 'button', 'value' => $strdelete));
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('form');
        $PAGE->requires->js_init_call('M.mod_programming.init_reports_detail');
    }

/// Finish the page
    echo $OUTPUT->footer($course);

function get_submits() {
    global $CFG, $DB, $page, $perpage, $programming, $course;
    global $firstinitial, $lastinitial, $latestonly, $groupid, $language;
    global $judgeresult;

    $submits = 0;
    $total = 0;
    if ($latestonly) {
        $rfrom = ", {programming_result} AS pr";
        $rwhere = " AND pr.programmingid = {$programming->id}
                    AND pr.latestsubmitid = ps.id";
    } else {
        $rfrom = $rwhere = '';
    }
    if ($firstinitial || $lastinitial) {
        $ufrom = ", {user} AS u";
        $uwhere = " AND u.firstnameletter LIKE '{$firstinitial}%'
                    AND u.lastnameletter LIKE '{$lastinitial}%'
                    AND u.id = ps.userid";
    } else {
        $ufrom = $uwhere = '';
    }
    if ($groupid) {
        $gfrom = ", {groups_members} AS gm";
        $gwhere = " AND gm.groupid = $groupid AND gm.userid = ps.userid";
    } else {
        $gfrom = $gwhere = '';
    }
    if ($judgeresult) {
        if ($judgeresult == 'NULL') {
            $jrwhere = " AND (ps.judgeresult IS NULL OR ps.judgeresult = '')";
        } else {
            $jrwhere = " AND ps.judgeresult = '$judgeresult'";
        }
    } else {
        $jrwhere = '';
    }

    $crit = " FROM {programming_submits} AS ps
                   $ufrom $rfrom $gfrom
             WHERE ps.programmingid = {$programming->id}
                   $uwhere $rwhere $gwhere $jrwhere
          ORDER BY ps.timemodified DESC";
    $sql = "SELECT ps.* $crit";
    $submits = $DB->get_records_sql($sql, null, $page * $perpage, $perpage);
    $sql = "SELECT COUNT(*) $crit";
    $total = $DB->count_records_sql($sql);

    return array($submits, $total);
}

function build_result_table($submits, $total) {
    global $CFG, $DB, $PAGE, $OUTPUT, $page, $perpage, $programming, $course, $cm;
    global $viewotherresult, $viewotherprogram, $deleteothersubmit, $rejudge;

    $table = new html_table();
    $headers = array(
        get_string('ID', 'programming'),
        get_string('submittime', 'programming'),
        get_string('fullname'),
        get_string('language', 'programming'),
        get_string('programcode', 'programming'),
        get_string('result', 'programming'),
        get_string('timeused', 'programming'),
        get_string('memused', 'programming'),
        );
    if ($deleteothersubmit || $rejudge) $headers[] = get_string('select');
    $table->head = $headers;
    $table->colclasses[2] = 'fullname';

    $table->attributes = array('id' => 'detail-table', 'class' => 'generaltable');
    $table->tablealign = 'center';
    $table->cellpadding = 3;
    $table->cellspacing = 1;

    $lang = $DB->get_records('programming_languages');
    foreach ($submits as $submit) {
        $data = array();
        $data[] = $submit->id;
        $data[] = userdate($submit->timemodified, '%Y-%m-%d %H:%M:%S');
        $user = $DB->get_record('user', array('id' => $submit->userid));
        $data[] = $OUTPUT->user_picture($user)."<a href='{$CFG->wwwroot}/user/view.php?id={$submit->userid}&amp;course={$course->id}'>".fullname($user).'</a>';
        $data[] = $lang[$submit->language]->name;
        if ($viewotherprogram) {
            $url = new moodle_url('../history.php', array('id' => $cm->id, 'userid' => $submit->userid, 'submitid' => $submit->id));
            $data[] = $OUTPUT->action_link($url, get_string('sizelines', 'programming', $submit));
        } else {
            $data[] = get_string('sizelines', 'programming', $submit);
        }
        if ($submit->judgeresult) {
            $strresult = get_string($submit->judgeresult, 'programming');
            if ($viewotherresult) {
                $url = new moodle_url('../result.php', array('id' => $cm->id, 'submitid' => $submit->id));
                $data[] = $OUTPUT->action_link($url, $strresult);
            } else {
                $data[] = $strresult;
            }
        } else {
            $data[] = '';
        }
        if ($submit->timeused != null) {
            $data[] = round($submit->timeused, 3);
        } else {
            $data[] = '';
        }
        if ($submit->memused != null) {
            $data[] = get_string('memusednk', 'programming', $submit->memused);
        } else {
            $data[] = '';
        }
        if ($deleteothersubmit || $rejudge) {
            $data[] = html_writer::empty_tag('input', array('class' => 'selectsubmit', 'type' => 'checkbox', 'name' => 'submitid[]', 'value' => $submit->id));
        }
        $table->data[] = $data;
    }

    return $table;
}

function build_filters() {
    global $OUTPUT, $DB;
    global $perpage, $page, $cm, $course;

    $filters = array();

    // select range
    $filters['latestonly'] = array(
        'title' => get_string('range', 'programming'),
        'options' => array('0' => get_string('showall', 'programming'),
                           '1' => get_string('showlatestonly', 'programming')));

    $options = programming_judgeresult_options(true);
    $options['NULL'] = get_string('statusshortnew', 'programming');
    $filters['judgeresult'] = array(
        'title' => get_string('judgeresult', 'programming'),
        'options' => $options);

    $groups = $DB->get_records('groups', array('courseid' => $course->id));
    if (is_array($groups)) {
        $options = array('' => get_string('all'));
        foreach ($groups as $group) {
            $options[$group->id] = $group->name;
        }
        $filters['group'] = array(
            'title' => get_string('groups'),
            'options' => $options);
    }

    $alphabet = explode(',', get_string('alphabet', 'langconfig'));
    $options = array('' => get_string('all'));
    foreach ($alphabet as $a) {
        $options[$a] = $a;
    }
    $filters['firstinitial'] = array(
        'title' => get_string('firstname'),
        'options' => $options);
    $filters['lastinitial'] = array(
        'title' => get_string('lastname'),
        'options' => $options);

    $options = array(10 => 10, 20 => 20, 30 => 30, 50 => 50, 100 => 100);
    $filters['perpage'] = array(
        'title' => get_string('showperpage', 'programming'),
        'options' => $options);

    return $filters;
}

?>
