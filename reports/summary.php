<?PHP

    require_once('../../../config.php');
    require_once('../lib.php');
    require_once($CFG->dirroot.'/lib/tablelib.php');

    $id = optional_param('id', 0, PARAM_INT);    // Course Module ID, or

    $params = array();
    if ($id) {
        $params['id'] = $id;
    }
    $PAGE->set_url('/mod/programming/reports/summary.php', $params);

    if ($id) {
        if (! $cm = get_coursemodule_from_id('programming', $id)) {
            print_error('Course Module ID was incorrect');
        }
    
        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error('Course is misconfigured');
        }
    
        if (! $programming = $DB->get_record('programming', array('id' => $cm->instance))) {
            print_error('Course module is incorrect');
        }
    }

    require_login($course->id, true, $cm);
    $context = context_module::instance($cm->id);

    require_capability('mod/programming:viewreport', $context);

    // results is stored in a array
    $stat_results = array();
    $groupnum = $DB->count_records('groups', array('courseid' => $course->id));
    $groups = $DB->get_records('groups', array('courseid' => $course->id));
    if (is_array($groups)) {
        foreach($groups as $group) {
            summary_stat($stat_results, $group);
        }
    }
    summary_stat($stat_results);

/// Print the page header
    $PAGE->set_title($programming->name);
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();

/// Print tabs
    $renderer = $PAGE->get_renderer('mod_programming');
    $tabs = programming_navtab('reports', 'reports-summary', $course, $programming, $cm);
    echo $renderer->render_navtab($tabs);

/// Print the main part of the page
    echo html_writer::tag('h2', get_string('summary', 'programming'));
    print_summary_table($stat_results);
    print_action_table();
    print_summary_chart($stat_results);

/// Finish the page
    echo $OUTPUT->footer($course);

function print_summary_table($stat_results) {
    global $CFG, $DB, $course, $params;

    $student_role = $DB->get_record('role', array('archetype' => 'student'));

    $table = new flexible_table('summary-stat-table');
    $def = array('range', 'studentcount', 'submitcount', 'submitpercent', 'compilecount', 'compilepercent', 'passedcount', 'passedpercent', 'intimepassedcount', 'intimepassedpercent', 'codelines');
    $table->define_columns($def);
    $headers = array(
        get_string('statrange', 'programming'),
        get_string('statstudentcount', 'programming', $student_role->name),
        get_string('statsubmitcount', 'programming'),
        '%',
        get_string('statcompiledcount', 'programming'),
        '%',
        get_string('statpassedcount', 'programming'),
        '%',
        get_string('statintimepassedcount', 'programming'),
        '%',
        get_string('stataveragelines', 'programming'));
    $table->define_headers($headers);

    $table->set_attribute('id', 'summary-stat-table');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('cellspacing', '1');
    $table->define_baseurl('/mod/programming/view.php', $params);
    $table->setup();

    foreach ($stat_results as $row) {
        $data = array();
        $data[] = $row['name'];
        $data[] = $row['studentcount'];
        $data[] = $row['submitcount'];
        $data[] = ($row['studentcount'] > 0 ? round($row['submitcount'] / $row['studentcount'] * 100, 0) : 0).'%';
        $data[] = $row['compiledcount'];
        $data[] = ($row['studentcount'] > 0 ? round($row['compiledcount'] / $row['studentcount'] * 100, 0) : 0).'%';
        $data[] = $row['passedcount'];
        $data[] = ($row['studentcount'] > 0 ? round($row['passedcount'] / $row['studentcount'] * 100, 0) : 0).'%';
        $data[] = $row['intimepassedcount'];
        $data[] = ($row['studentcount'] > 0 ? round($row['intimepassedcount'] / $row['studentcount'] * 100, 0) : 0).'%';
        $data[] = $row['submitcount'] > 0 ? round($row['averagelines']) : 0;
        $table->add_data($data);
    }

    $table->print_html();
}

function print_summary_chart($stat_results) {
    global $PAGE;

    $summary = array_pop($stat_results);
    $acintime = $summary['intimepassedcount'];
    $ac = $summary['passedcount'] - $summary['intimepassedcount'];
    $se = $summary['compiledcount'] - $summary['passedcount'];
    $ce = $summary['submitcount'] - $summary['compiledcount'];
    $ns = $summary['studentcount'] - $summary['submitcount'];
    $strresultcount = get_string('resultcountchart', 'programming');
    $stracintime = get_string('resultchartacintime', 'programming');
    $strac = get_string('resultchartacdiscount', 'programming');
    $strse = get_string('resultchartsomethingwrong', 'programming');
    $strce = get_string('resultchartcompileerror', 'programming');
    $strns = get_string('resultchartnosubmition', 'programming');
    $strgroupresultcount = get_string('resultgroupcountchart', 'programming');

    $jsmodule = array(
        'name'     => 'mod_programming',
        'fullpath' => '/mod/programming/module.js',
        'requires' => array('base', 'io', 'node', 'json', 'charts'),
        'strings' => array()
    );

    $groupcount = count($stat_results);
    if ($groupcount) {
        $data = array();
        foreach ($stat_results as $group) {
            $data[] = array(
                'category' => $group['name'],
                'acintime' => $group['intimepassedcount'],
                'ac' => $group['passedcount'] - $group['intimepassedcount'],
                'se' => $group['compiledcount'] - $group['passedcount'],
                'ce' => $group['submitcount'] - $group['compiledcount'],
            );
        }

        echo html_writer::tag('div', '', array('id' => 'summary-group-count-chart', 'class' => 'chart'));
        $PAGE->requires->js_init_call('M.mod_programming.draw_summary_group_count_chart', array(json_encode($data)), false, $jsmodule);
    }

    $percent_chart_data = array(
        array('result' => $stracintime, 'count' => $acintime),
        array('result' => $strac, 'count' => $ac),
        array('result' => $strse, 'count' => $se),
        array('result' => $strce, 'count' => $ce),
        array('result' => $strns, 'count' => $ns)
    );
    echo html_writer::tag('div', '', array('id' => 'summary-percent-chart', 'class' => 'chart'));
    $PAGE->requires->js_init_call('M.mod_programming.draw_summary_percent_chart', array(json_encode($percent_chart_data)), false, $jsmodule);
}

/**
 * 统计各个小组完成题目的情况。
 *
 * 目前此函数只处理 roleid 为 5 即学生的情况。
 *
 * @param $state_results 存储统计结果
 * @param $group 要统计的小组，如果为 null 则统计全部人员的情况
 */
function summary_stat(&$stat_results, $group = null) {
    global $USER, $CFG, $DB, $course, $programming;

    $context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $course->id));
    $roleid = 5;

    $student_role = $DB->get_record('role', array('archetype' => 'student'));

    if ($group) {
        $gfrom = ", {groups_members} AS gm";
        $gwhere = " AND gm.groupid = $group->id AND ra.userid = gm.userid ";
        $name = $group->name;
    } else {
        $gfrom = $gwhere = '';
        $name = get_string('allstudents', 'programming', $student_role->name);
    }

    $studentcount = $DB->count_records_sql("
        SELECT COUNT(*)
          FROM {role_assignments} AS ra
               $gfrom
         WHERE ra.roleid = $roleid
           AND ra.contextid = $context->id
               $gwhere");
    $submitcount = $DB->count_records_sql("
        SELECT COUNT(*)
          FROM {role_assignments} AS ra,
               {programming_result} AS pr
               $gfrom
         WHERE ra.roleid = $roleid
           AND ra.contextid = $context->id
           AND pr.programmingid = $programming->id
           AND ra.userid = pr.userid
               $gwhere");
    $compiledcount = $DB->count_records_sql("
        SELECT COUNT(*)
          FROM {role_assignments} AS ra,
               {programming_result} AS pr,
               {programming_submits} AS ps
               $gfrom
         WHERE ps.programmingid = $programming->id
           AND pr.programmingid = $programming->id
           AND ra.roleid = $roleid
           AND ra.contextid = $context->id
           AND ps.id = pr.latestsubmitid
           AND pr.userid = ra.userid
           AND ps.judgeresult != 'CE' AND ps.judgeresult != ''
               $gwhere");
    $passedcount = $DB->count_records_sql("
        SELECT COUNT(*)
          FROM {role_assignments} AS ra,
               {programming_submits} AS ps,
               {programming_result} AS pr
               $gfrom
         WHERE ps.programmingid = {$programming->id}
           AND pr.programmingid = {$programming->id}
           AND ra.roleid = $roleid
           AND ra.contextid = $context->id
           AND pr.userid = ra.userid
           AND pr.latestsubmitid = ps.id
           AND ps.passed = 1
               $gwhere");
    $intimepassedcount = $DB->count_records_sql("
        SELECT COUNT(*)
          FROM {role_assignments} AS ra,
               {programming_submits} AS ps,
               {programming_result} AS pr
               $gfrom
         WHERE ps.programmingid = {$programming->id}
           AND pr.programmingid = {$programming->id}
           AND ra.roleid = $roleid
           AND ra.contextid = $context->id
           AND pr.userid = ra.userid
           AND pr.latestsubmitid = ps.id
           AND ps.timemodified <= {$programming->timediscount}
           AND ps.passed = 1
               $gwhere");
    $codeavg = $DB->get_record_sql("
        SELECT AVG(codelines) as codelines
          FROM {role_assignments} AS ra,
               {programming_submits} AS ps,
               {programming_result} AS pr
               $gfrom
         WHERE ps.programmingid = {$programming->id}
           AND pr.programmingid = {$programming->id}
           AND pr.latestsubmitid = ps.id
           AND ra.roleid = $roleid
           AND ra.contextid = $context->id
           AND pr.userid = ra.userid
               $gwhere");
    $codeavg = intval($codeavg->codelines);
    array_push($stat_results,
        array('name' => $name,
              'studentcount' => $studentcount,
              'submitcount' => $submitcount,
              'compiledcount' => $compiledcount,
              'passedcount' => $passedcount,
              'intimepassedcount' => $intimepassedcount,
              'averagelines' => $codeavg));
    return;
}

function print_action_table() {
    global $CFG, $OUTPUT, $cm, $context;

    echo '<table><tr><td>';
    if (has_capability('mod/programming:viewotherprogram', $context)) {
        echo $OUTPUT->single_button(new moodle_url('/mod/programming/package.php', array('id' => $cm->id)), get_string('package', 'programming'), 'get');
    }
    echo '</td><td>';
    if (has_capability('mod/programming:edittestcase', $context)) {
        echo $OUTPUT->single_button(new moodle_url('/mod/programming/rejudge.php', array('id' => $cm->id)), get_string('rejudge', 'programming'), 'get');
    }
    echo '</td></tr></table>';
}

?>
