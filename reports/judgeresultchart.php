<?PHP

    require_once('../../../config.php');
    require_once('../lib.php');

    $id = required_param('id', PARAM_INT);     // programming ID
    $range = optional_param('range', 0, PARAM_INT);     // 0 for show all
    $groupid = optional_param('group', 0, PARAM_INT);   // 0 for show all

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
                    'range' => $range,
                    'group' => $groupid);
    $PAGE->set_url('/mod/programming/reports/judgeresultchart.php', $params);

    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);

    require_capability('mod/programming:viewreport', $context);


/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('reports', 'reports-judgeresultchart', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print the main part of the page
    $renderer = $PAGE->get_renderer('mod_programming');
    echo $renderer->render_filters(build_filters(), $PAGE->url, $params);
    print_judgeresult_chart();

/// Finish the page
    echo $OUTPUT->footer($course);

function count_judgeresult() {
    global $DB, $programming, $range, $groupid;

    $rfrom = $rwhere = '';
    if ($range == 1) {
        $rfrom = ", {programming_result} AS pr";
        $rwhere = " AND pr.programmingid = {$programming->id}
                    AND pr.latestsubmitid = ps.id";
    }
    $gfrom = $gwhere = '';
    if ($groupid) {
        $gfrom = ", {groups_members} AS gm";
        $gwhere = " AND gm.groupid = $groupid AND gm.userid = ps.userid";
    }
    
    $sql = "SELECT ps.judgeresult AS judgeresult,
                   COUNT(*) AS count
              FROM {programming_submits} AS ps
                   $rfrom $gfrom
             WHERE ps.programmingid = {$programming->id}
                   $rwhere $gwhere
          GROUP BY ps.judgeresult";
    $rst = $DB->get_recordset_sql($sql);
    $ret = array();
    foreach ($rst as $row) {
        $ret[$row->judgeresult] = $row->count;
    }
    return $ret;
}

function print_judgeresult_chart() {
    global $PAGE;

    $values = array();

    $c = count_judgeresult();
    $keys = array('AC', 'PE', 'WA', 'RE', 'FPE', 'KS', 'TLE', 'MLE', 'OLE', 'CE');
    foreach ($keys as $key) {
        $name = get_string($key, 'programming');
        if (!array_key_exists($key, $c)) $c[$key] = 0;
        $values[] = array('result' => $name, 'count' => $c[$key]);
        $c[$key] = 0;
    }

    $others = 0; foreach ($c as $key => $value) $others += $value;
    $name = get_string('others', 'programming');
    $values[] = array('result' => $name, 'count' => $others);

    $strjudgeresultchart = get_string('judgeresultcountchart', 'programming');
    $strvisitgoogleneeded = get_string('visitgoogleneeded', 'programming');

    $jsmodule = array(
        'name'     => 'mod_programming',
        'fullpath' => '/mod/programming/module.js',
        'requires' => array('base', 'io', 'node', 'json', 'charts'),
        'strings' => array()
    );

    echo html_writer::tag('div', '', array('id' => 'judgeresult-chart', 'class' => 'chart'));
    $PAGE->requires->js_init_call('M.mod_programming.draw_judgeresult_chart', array(json_encode($values)), false, $jsmodule);
}

function build_filters() {
    global $OUTPUT, $DB;
    global $perpage, $page, $cm, $course;

    $filters = array();

    // select range
    $filters['range'] = array(
        'title' => get_string('range', 'programming'),
        'options' => array('0' => get_string('showall', 'programming'),
                           '1' => get_string('showlatestonly', 'programming')));

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

    return $filters;
}
