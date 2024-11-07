<?php

    require_once('../../../config.php');
    require_once('../lib.php');
    require_once($CFG->dirroot.'/lib/tablelib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $page = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 10, PARAM_INT);
    $tsort = optional_param('tsort', 'timemodified', PARAM_CLEAN);
    $language = optional_param('language', '', PARAM_INT);
    $groupid = optional_param('group', 0, PARAM_INT);

    if (! $cm = get_coursemodule_from_id('programming', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
        print_error('invalidprogrammingid', 'programming');
    }

    $params = array('id' => $cm->id,
                    'page' => $page,
                    'perpage' => $perpage,
                    'tsort' => $tsort,
                    'language' => $language,
                    'group' => $groupid);

    $PAGE->set_url('/mod/programming/reports/best.php', $params);

    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);

    require_capability('mod/programming:viewreport', $context);
    $viewotherresult = has_capability('mod/programming:viewotherresult', $context);
    $viewotherprogram = has_capability('mod/programming:viewotherprogram', $context);


/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('reports', 'reports-best', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print the main part of the page
    $renderer = $PAGE->get_renderer('mod_programming');

    echo html_writer::tag('h2', get_string('allprograms', 'programming'));
    echo $renderer->render_filters(build_filters(), $PAGE->url, $params);
    
    print_submit_table();

/// Finish the page
    echo $OUTPUT->footer($course);

function build_filters() {
    global $OUTPUT, $DB;
    global $perpage, $page, $cm, $course;

    $filters = array();

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

    $languages = $DB->get_records('programming_languages');
    if (is_array($languages)) {
        $options = array('' => get_string('all'));
        foreach ($languages as $language) {
            $options[$language->id] = $language->name;
        }
        $filters['language'] = array(
            'title' => get_string('language', 'programming'),
            'options' => $options);
    }

    $options = array(10 => 10, 20 => 20, 30 => 30, 50 => 50, 100 => 100);
    $filters['perpage'] = array(
        'title' => get_string('showperpage', 'programming'),
        'options' => $options);

    return $filters;
}

function get_submits($orderby) {
    global $CFG, $DB, $page, $perpage, $programming, $course, $language, $groupid;

    $gfrom = $gwhere = '';
    if ($groupid) {
        $gfrom = ", {$CFG->prefix}groups_members AS gm";
        $gwhere = " AND gm.groupid = $groupid AND gm.userid = ps.userid";
    }

    $lwhere = '';
    if ($language) {
        $lwhere = " AND ps.language = $language";
    }
    
    $submits = 0;
    $total = 0;
    $crit = " FROM {$CFG->prefix}programming_submits AS ps,
                   {$CFG->prefix}programming_result AS pr
                   $gfrom
             WHERE ps.programmingid = {$programming->id}
               AND pr.programmingid = {$programming->id}
               AND pr.latestsubmitid = ps.id
               AND ps.judgeresult = 'AC'
                   $gwhere $lwhere
          ORDER BY $orderby";
    $sql = "SELECT ps.* $crit";
    $submits = $DB->get_records_sql($sql, null, $page * $perpage, $perpage);
    $sql = "SELECT COUNT(*) $crit";
    $total = $DB->count_records_sql($sql);

    return array($submits, $total);
}

function print_submit_table() {
    global $CFG, $DB, $PAGE, $OUTPUT;
    global $page, $perpage, $programming, $course, $language, $groupid;
    global $viewotherresult, $viewotherprogram;

    $table = new flexible_table('detail-table');
    $def = array('rank', 'ps.timemodified', 'user', 'language', 'code', 'ps.timeused', 'ps.memused');
    $table->define_columns($def);
    $headers = array(
        get_string('rank', 'programming'),
        get_string('submittime', 'programming'),
        get_string('fullname'),
        get_string('language', 'programming'),
        get_string('programcode', 'programming'),
        get_string('timeused', 'programming'),
        get_string('memused', 'programming'),
        );
    $table->define_headers($headers);

    $table->baseurl = $PAGE->url;
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'detail-table');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('align', 'center');
    $table->set_attribute('cellpadding', '3');
    $table->set_attribute('cellspacing', '1');
    $table->sortable(true, 'ps.timeused');
    $table->no_sorting('rank');
    $table->no_sorting('user');
    $table->no_sorting('language');
    $table->no_sorting('code');
    $table->column_class('user', 'fullname');
    $table->setup();
    $orderby = $table->get_sql_sort();

    list($submits, $totalcount) = get_submits($orderby);
    if (is_array($submits)) {
        $i = 0;
        $lang = $DB->get_records('programming_languages');
        foreach ($submits as $submit) {
            $data = array();
            $data[] = ++$i;
            $data[] = userdate($submit->timemodified, '%Y-%m-%d %H:%M:%S');
            $user = $DB->get_record('user', array('id' => $submit->userid));
            $data[] = $OUTPUT->user_picture($user)."<a href='{$CFG->wwwroot}/user/view.php?id={$submit->userid}&amp;course={$course->id}'>".fullname($user).'</a>';
            $data[] = $lang[$submit->language]->name;
            if ($viewotherprogram) {
                $data[] = "<a href='{$CFG->wwwroot}/mod/programming/history.php?a={$programming->id}&amp;userid={$submit->userid}&amp;submitid={$submit->id}'>".get_string('sizelines', 'programming', $submit).'</a>';
            } else {
                $data[] = get_string('sizelines', 'programming', $submit);
            }
            if ($submit->judgeresult) {
                $data[] = round($submit->timeused, 3);
                $data[] = get_string('memusednk', 'programming', $submit->memused);
            } else {
                $data[] = ''; $data[] = ''; $data[] = '';
            }
            $table->add_data($data);
        }

    }

    $table->print_html();

    $pagingbar = new paging_bar($totalcount, $page, $perpage, $PAGE->url, 'page');
    echo $OUTPUT->render($pagingbar);
}

?>
