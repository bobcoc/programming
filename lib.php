<?PHP

// $Id: lib.php,v 1.1 2012/07/10 05:37:08 sunshine Exp $
/// Library of functions and constants for module programming
/// (replace programming with the name of your module and delete this line)
require_once($CFG->dirroot . '/lib/datalib.php');

define('PROGRAMMING_STATUS_NEW', 0);
define('PROGRAMMING_STATUS_COMPILING', 1);
define('PROGRAMMING_STATUS_COMPILEOK', 2);
define('PROGRAMMING_STATUS_RUNNING', 3);
define('PROGRAMMING_STATUS_WAITING', 4);
define('PROGRAMMING_STATUS_FINISH', 10);
define('PROGRAMMING_STATUS_COMPILEFAIL', 11);
define('PROGRAMMING_MAX_ATTEMPTS', 6);

define('PROGRAMMING_SHOWMODE_NORMAL', 1);
define('PROGRAMMING_SHOWMODE_CONTEST', 2);

define('PROGRAMMING_RESEMBLE_DELETED', -1);
define('PROGRAMMING_RESEMBLE_NEW', 0);
define('PROGRAMMING_RESEMBLE_WARNED', 1);
define('PROGRAMMING_RESEMBLE_CONFIRMED', 2);
define('PROGRAMMING_RESEMBLE_FLAG1', 11);
define('PROGRAMMING_RESEMBLE_FLAG2', 12);
define('PROGRAMMING_RESEMBLE_FLAG3', 13);

define('PROGRAMMING_TEST_SHOWAFTERDISCOUNT', -2);
define('PROGRAMMING_TEST_HIDDEN', -1);
define('PROGRAMMING_TEST_SHOWINRESULT', 0);
define('PROGRAMMING_TEST_SHOW', 1);

define('PROGRAMMING_PRESET_BEGIN', "/* PRESET CODE BEGIN - NEVER TOUCH CODE BELOW */");
define('PROGRAMMING_PRESET_END', "/* PRESET CODE END - NEVER TOUCH CODE ABOVE */");

define('PROGRAMMING_RANGE_ALL', 0);
define('PROGRAMMING_RANGE_LATEST', 1);

function programming_add_instance($programming) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.
    global $DB;

    $programming->timemodified = time();

    if ($programming->inputs == 0)
        $programming->inputfile = '';
    if ($programming->outputs == 0)
        $programming->outputfile = '';
    $id = $DB->insert_record('programming', $programming);

    if (isset($programming->langlimit) && is_array($programming->langlimit)) {
        foreach ($programming->langlimit as $lang) {
            $pl = new stdClass();
            $pl->programmingid = $id;
            $pl->languageid = $lang;
            $DB->insert_record('programming_langlimit', $pl);
        }
    }
    if (isset($programming->category) && is_array($programming->category)) {
        foreach ($programming->category as $cat) {
            $pc = new stdClass();
            $pc->pid = $id;
            $pc->catid = $cat;
            $DB->insert_record('programming_catproblemmap', $pc);
        }
    }
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir . '/gradelib.php');
    }
    $params = array('itemname' => $programming->name,
        'grademax' => $programming->grade,
        'grademin' => 0,
        'gradetype' => GRADE_TYPE_VALUE);
    grade_update('mod/programming', $programming->course, 'mod', 'programming', $id, 0, NULL, $params);
    return $id;
}

function programming_update_instance($programming) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.
    global $DB;

    $programming->timemodified = time();
    $programming->id = $programming->instance;

    if ($programming->inputs == 0)
        $programming->inputfile = '';
    if ($programming->outputs == 0)
        $programming->outputfile = '';

    if (isset($programming->keeplatestonly) and $programming->keeplatestonly) {
        programming_delete_old_submits($programming->id);
    }

    // Update the langlimit table
    $changed = false;
    if (isset($programming->langlimit) && is_array($programming->langlimit)) {
        $newlangs = $programming->langlimit;
    } else {
        $newlangs = array();
    }

    $langs = array();
    $rows = $DB->get_records('programming_langlimit', array('programmingid' => $programming->id));
    if (is_array($rows)) {
        foreach ($rows as $row) {
            $langs[] = $row->languageid;
        }
    }

    foreach (array_diff($langs, $newlangs) as $lang) {
        $DB->delete_records('programming_langlimit', array('programmingid' => $programming->id, 'languageid' => $lang));
        $changed = true;
    }

    foreach (array_diff($newlangs, $langs) as $lang) {
        $pl = new stdClass();
        $pl->programmingid = $programming->id;
        $pl->languageid = $lang;
        $DB->insert_record('programming_langlimit', $pl);
        $changed = true;
    }

    if (isset($programming->category) && is_array($programming->category)) {
        $newcats = $programming->category;
    } else {
        $newcats = array();
    }

    $cats = array();
    $rows1 = $DB->get_records('programming_catproblemmap', array('pid' => $programming->id));
    if (is_array($rows1)) {
        foreach ($rows1 as $row) {
            $cats[] = $row->catid;
        }
    }

    foreach (array_diff($cats, $newcats) as $cat) {
        $DB->delete_records('programming_catproblemmap', array('pid' => $programming->id, 'catid' => $cat));
        $changed = true;
    }

    foreach (array_diff($newcats, $cats) as $cat) {
        $pc = new stdClass();
        $pc->pid = $programming->id;
        $pc->catid = $cat;
        $DB->insert_record('programming_catproblemmap', $pc);
        $changed = true;
    }
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir . '/gradelib.php');
    }
    $params = array('itemname' => $programming->name,
        'grademax' => $programming->grade,
        'grademin' => 0,
        'gradetype' => GRADE_TYPE_VALUE);
    grade_update('mod/programming', $programming->course, 'mod', 'programming', $programming->id, 0, NULL, $params);
    return $DB->update_record('programming', $programming) or $changed;
}

function programming_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  
    global $DB;

    if (!$programming = $DB->get_record('programming', array('id' => $id))) {
        return false;
    }

    $result = true;

    //判断是否有引用该实例 如果没有则删除
    $sql = "SELECT 
                * FROM {modules} m,{course_modules} cm 
            WHERE m.id=cm.module AND m.name='programming' AND cm.instance=?";
    $params = array($programming->id);
    $ps = $DB->get_records_sql($sql, $params);
    if (count($ps) <= 1) {

        # Delete any dependent records here #
        if (!$DB->delete_records('programming_langlimit', array('programmingid' => $programming->id))) {
            $result = false;
        }

        if (!$DB->delete_records('programming', array('id' => $programming->id))) {
            $result = false;
        }
    }
    return $result;
}

function programming_user_outline($course, $user, $mod, $programming) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

    global $DB;

    $return = new stdClass();
    $params = array($programming->id, $user->id);
    $sql = "SELECT ps.*
          FROM {programming_submits} AS ps,
               {programming_result} AS pr
         WHERE pr.programmingid = ?
           AND pr.userid = ?
           AND pr.latestsubmitid = ps.id";
    $ps = $DB->get_records_sql($sql, $params);

    if (!empty($ps)) {
        $ps = array_shift($ps);
        if ($programming->showmode == PROGRAMMING_SHOWMODE_NORMAL) {
            if ($ps->status == PROGRAMMING_STATUS_FINISH) {
                $return->info = programming_get_test_results_short($ps);
            } else {
                $return->info = programming_get_submit_status_short($ps);
            }
        } else {
            if ($ps->status == PROGRAMMING_STATUS_FINISH) {
                $results = $DB->get_records('programming_test_results', array('submitid' => $ps->id));
                $return->info = programming_contest_get_judgeresult($results);
            } else if ($ps->status == PROGRAMMING_STATUS_COMPILEFAIL) {
                $return->info = get_string('CE', 'programming');
            }
        }
        $return->time = $ps->timemodified;
    }

    return $return;
}

function programming_user_complete($course, $user, $mod, $programming) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.

    return true;
}

function programming_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in programming activities and print it out. 
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

function programming_cron() {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

    global $CFG;

    return true;
}

function programming_grades($programmingid) {
/// Must return an array of grades for a given instance of this module, 
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;

    global $CFG, $DB;

    $return = new stdClass;
    $programming = $DB->get_record('programming', array('id' => $programmingid));
    $return->maxgrade = $programming->grade; // find the maximum allowed grade;
    $return->grades = array();

    // get the summary of weight of all the test
    $select = 'programmingid = :pid';
    $params = array('pid' => $programmingid);
    $total_weight = $DB->get_field_select('block_rss_client', 'SUM(weight)', $select, $params);
    $total_weight = $total_weight ? 1.0 / $total_weight : 0;

    // get weight summary of each submit
    $params = array($programming->grade, $total_weight, $programming->discount,
        $programmingid, $programmingid, $programmingid, $programming->timediscount,
        $programmingid, $programmingid);

    $query = "
        SELECT w.userid AS userid,
               round(? * w.weight * ? * (1 - d.discount * (1 - ? / 10.0)), 2) AS grade
          FROM (
            SELECT s.userid AS userid,
                   SUM(tr.passed * t.weight) AS weight
              FROM {programming_result} AS r,
                   {programming_submits} AS s,
                   {programming_test_results} AS tr,
                   {programming_tests} AS t
             WHERE r.programmingid = ?
               AND s.programmingid = ?
               AND t.programmingid = ?
               AND r.latestsubmitid=s.id
               AND s.id=tr.submitid
               AND tr.testid=t.id
          GROUP BY s.userid) AS w, (
            SELECT s.userid AS userid,
                   s.timemodified > ? AS discount
              FROM {programming_result} AS r,
                   {programming_submits} AS s
             WHERE r.programmingid = ?
               AND s.programmingid = ?
               AND r.latestsubmitid=s.id
          GROUP BY s.userid) AS d
         WHERE d.userid = w.userid
      ";
    //print $query;
    $grades = $DB->get_records_sql($query, $params);

    if (is_array($grades)) {
        foreach ($grades as $grade) {
            $return->grades[$grade->userid] = $grade->grade;
        }
    }

    return $return;
}

/**
 * Update the grade of a submission.
 *
 * @param $submitid ID of submission.
 */
function programming_update_grade($submitid) {
    global $CFG, $DB;

    $submit = $DB->get_record('programming_submits', array('id' => $submitid));
    if (empty($submit))
        return;

    # do not update grade if this is not the latest submission
    $rst = $DB->get_record('programming_result', array('programmingid' => $submit->programmingid, 'userid' => $submit->userid));
    if ($rst->latestsubmitid != $submitid)
        return;

    $p = $DB->get_record('programming', array('id' => $submit->programmingid));
    if (empty($p))
        return;

    $grade = 0;
    if ($submit->judgeresult != 'CE') {
        // get the summary of weight of all the test
        $total_weight = $DB->get_field_select('programming_tests', 'SUM(weight)', 'programmingid=?', array($submit->programmingid));
        if ($total_weight == 0)
            $total_weight = 1;

        $sql = "SELECT SUM(pt.weight) AS weight
                      FROM {programming_test_results} AS ptr,
                           {programming_tests} AS pt
                     WHERE ptr.submitid = ?
                       AND ptr.passed = 1
                       AND pt.programmingid = ?
                       AND ptr.testid = pt.id";
        $weight = $DB->get_field_sql($sql, array($submit->id, $submit->programmingid));
        $grade = $p->grade * $weight / $total_weight;
        if ($submit->timemodified > $p->timediscount) {
            $grade = $grade * $p->discount / 10.0;
        }
    }
    programming_grade_item_update($p, $submit, $grade);
}

function programming_grade_item_update($programming, $submit, $grade) {
    global $CFG;

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir . '/gradelib.php');
    }

    $cm = get_coursemodule_from_instance('programming', $programming->id, $programming->course);

    $params = array('itemname' => $programming->name,
        'idnumber' => $cm->id,
        'grademax' => $programming->grade,
        'grademin' => 0,
        'gradetype' => GRADE_TYPE_VALUE);
    $grades = new stdClass();
    $grades->itemid = $cm->id;
    $grades->userid = $submit->userid;
    $grades->rawgrade = $grade;

    return grade_update('mod/programming', $programming->course, 'mod', 'programming', $programming->id, 0, $grades, $params);
}

function programming_get_participants($programmingid) {
//Must return an array of user records (all data) who are participants
//for a given instance of programming. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

    global $CFG, $DB;
    $params = array($programmingid);
    $sql = "SELECT DISTINCT u.*
              FROM {programming_submits} AS ps,
                   {user} AS u,
              WHERE ps.programmingid = ?
               AND ps.userid = u.id";
    $users = $DB->get_records_sql($sql, $params);

    return $users;
}

function programming_scale_used($programmingid, $scaleid) {
//This function returns if a scale is being used by one programming
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.

    $return = false;

    //$rec = get_record("programming","id","$programmingid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other programming functions go here.  Each of them must have a name that 
/// starts with programming_

function programming_get_showmode_options() {
    $options = array();
    $options['1'] = get_string('normalmode', 'programming');
    $options['2'] = get_string('contestmode', 'programming');
    return $options;
}

function programming_get_yesno_options() {
    $yesnooptions = array();
    $yesnooptions[0] = get_string('no');
    $yesnooptions[1] = get_string('yes');
    return $yesnooptions;
}

function programming_get_timelimit_options($default = 0) {
    $timelimitoptions = array();
    $timelimitoptions[0] = get_string('timelimitunlimited', 'programming');
    for ($i = 1; $i <= 30; $i += 1) {
        $timelimitoptions[$i] = get_string('nseconds', 'programming', $i);
    }
    return $timelimitoptions;
}

function programming_get_difficulty_options($default = 0) {
    $difficultyoptions = array();
    for ($i = 0; $i <= 10; $i += 1) {
        $difficultyoptions[$i] = $i;
    }
    return $difficultyoptions;
}

function programming_testcase_pub_options() {
    $options = array();
    $options[PROGRAMMING_TEST_SHOWAFTERDISCOUNT] = get_string('afterdiscount', 'programming');
    $options[PROGRAMMING_TEST_HIDDEN] = get_string('never', 'programming');
    $options[PROGRAMMING_TEST_SHOWINRESULT] = get_string('inresult', 'programming');
    $options[PROGRAMMING_TEST_SHOW] = get_string('always', 'programming');
    return $options;
}

function programming_testcase_pub_getstring($pub) {
    $options = programming_testcase_pub_options();
    return $options[$pub];
}

function programming_testcase_require_view_capability($context, $case, $inresult = false, $afterdiscount = false) {
    if ($case->pub == PROGRAMMING_TEST_HIDDEN ||
            $case->pub == PROGRAMMING_TEST_SHOWINRESULT && $inresult ||
            $case->pub == PROGRAMMING_TEST_SHOWAFTERDISCOUNT && $afterdiscount) {
        require_capability('mod/programming:viewhiddentestcase', $context);
    } else {
        require_capability('mod/programming:viewpubtestcase', $context);
    }
}

function programming_testcase_visible($tests, $result, $inresult = false, $afterdiscount = false) {
    if (!is_object($result)) {
        return false;
    }

    $pub = $tests[$result->testid]->pub;
    return $pub == PROGRAMMING_TEST_SHOW ||
            $pub == PROGRAMMING_TEST_SHOWINRESULT && $inresult ||
            $pub == PROGRAMMING_TEST_SHOWAFTERDISCOUNT && $afterdiscount;
}

function programming_get_weight_options() {
    $weightoptions = array();
    //$weightoptions[0] = get_string('weightsetting', 'programming');
    for ($i = 0; $i <= 10; $i += 1) {
        $weightoptions[$i] = $i;
    }
    return $weightoptions;
}

function programming_get_memlimit_options() {
    $memlimitoptions = array();
    $memlimitoptions[0] = get_string('memlimitunlimited', 'programming');
    for ($i = 256; $i <= 512; $i += 32) {
        $memlimitoptions[$i] = $i . 'KB';
    }
    for ($i = 1; $i < 10; $i++) {
        $memlimitoptions[$i * 1024] = $i . 'MB';
    }
    for ($i = 10; $i <= 550; $i+= 10) {
        $memlimitoptions[$i * 1024] = $i . 'MB';
    }
    return $memlimitoptions;
}

function programming_get_nproc_options() {
    $options = array();
    for ($i = 0; $i <= 16; $i++) {
        $options[$i] = "$i";
    }
    return $options;
}

function programming_get_language_options($programming = False) {
    global $CFG, $DB;
    if ($programming) {
        $params = array($programming->id);
        $sql = "SELECT * FROM {$CFG->prefix}programming_languages
                 WHERE id in (
                SELECT languageid
                  FROM {programming_langlimit}
                 WHERE programmingid=?)";
        $languages = $DB->get_records_sql($sql, $params);
        if (!is_array($languages) || count($languages) == 0) {
            $languages = $DB->get_records('programming_languages');
        }
    } else {
        $languages = $DB->get_records('programming_languages');
    }
    $languageoptions = array();
    foreach ($languages as $id => $row) {
        // $languageoptions[$id] = $row->name;
        $languageoptions[$id] = $row->description;
    }
    return $languageoptions;
}

function programming_get_category_options($programming = False) {
    global $DB;
    if ($programming) {
        $params = array($programming->id);
        $sql = "SELECT * FROM {programming_category}
                 WHERE catid in (
                SELECT catid
                  FROM {programming_catproblemmap}
                 WHERE pid=?)";
        $cats = $DB->get_records_sql($sql, $params);
        if (!is_array($cats) || count($cats) == 0) {
            $cats = $DB->get_records('programming_category');
        }
    } else {
        $cats = $DB->get_records('programming_category');
    }
    $catoptions = array();
    foreach ($cats as $id => $row) {
        // $languageoptions[$id] = $row->name;
        $catoptions[$id] = $row->catname;
    }
    return $catoptions;
}

function programming_get_language_options_js($programming = False) {
    global $CFG, $DB;
    if ($programming) {
        $params = array($programming->id);
        $sql = "SELECT * FROM {$CFG->prefix}programming_languages
                 WHERE id in (
                SELECT languageid
                  FROM {programming_langlimit}
                 WHERE programmingid=?)";
        $languages = $DB->get_records_sql($sql, $params);
        if (!is_array($languages) || count($languages) == 0) {
            $languages = $DB->get_records('programming_languages');
        }
    } else {
        $languages = $DB->get_records('programming_languages');
    }
    $languageoptions = array();
    foreach ($languages as $id => $row) {
        // $languageoptions[$id] = $row->name;
        $languageoptions[$id] = $row->langmode;
    }
    return $languageoptions;
}

function programming_test_add_instance($testcase) {
    global $DB;
    if (strlen($testcase->input) > 1024) {
        $testcase->gzinput = bzcompress($testcase->input);
        $testcase->input = mb_substr($testcase->input, 0, 1024, 'UTF-8');
    }
    if (strlen($testcase->output) > 1024) {
        $testcase->gzoutput = bzcompress($testcase->output);
        $testcase->output = mb_substr($testcase->output, 0, 1024, 'UTF-8');
    }
    $testcase->timemodified = time();
    return $DB->insert_record("programming_tests", $testcase);
}

function programming_test_update_instance($testcase) {
    # If input is not provided, do not update input and gzinput
    global $DB;
    if (empty($testcase->input)) {
        unset($testcase->input);
        unset($testcase->gzinput);
    } else {
        $testcase->gzinput = bzcompress($testcase->input);
        $testcase->input = mb_substr($testcase->input, 0, 1024, 'UTF-8');
    }
    # If output is not provided, do not update output and gzoutput
    if (empty($testcase->output)) {
        unset($testcase->output);
        unset($testcase->gzoutput);
    } else {
        $testcase->gzoutput = bzcompress($testcase->output);
        $testcase->output = mb_substr($testcase->output, 0, 1024, 'UTF-8');
    }

    $testcase->timemodified = time();
    return $DB->update_record('programming_tests', $testcase);
}

function programming_submit_add_instance($programming, $submit) {
    global $CFG, $USER, $DB;

    $submit->timemodified = time();
    $submit->codelines = count(explode("\n", $submit->code));
    $submit->codesize = strlen($submit->code);
    $submit->status = 0;
    $result = $DB->insert_record('programming_submits', $submit);

    # update programming_result
    if ($result) {
        $r = $DB->get_record('programming_result', array('programmingid' => $programming->id, 'userid' => $USER->id));
        if ($r) {
            $r->submitcount++;
            $r->latestsubmitid = $result;
            $result2 = $DB->update_record('programming_result', $r);
        } else {
            $r = new stdClass;
            $r->programmingid = $programming->id;
            $r->userid = $submit->userid;
            $r->submitcount = 1;
            $r->latestsubmitid = $result;
            $result2 = $DB->insert_record('programming_result', $r);
        }
    }

    # update tester
    if ($result) {
        $DB->execute("INSERT INTO {programming_testers} (submitid, testerid) VALUES ($result, 0)");
    }

    # delete the old submits
    if ($result and $programming->keeplatestonly) {
        programming_delete_old_submits($programming->id, $submit->userid);
    }

    return $result;
}

function programming_delete_old_submits($programmingid = -1, $userid = -1) {
    global $CFG, $DB;

    if ($programmingid > 0) {
        if ($userid >= 0) {
            $params = array($programmingid, $userid);

            $sql = "SELECT id, programmingid, userid FROM
            {programming_submits} WHERE 
            programmingid = ? AND userid = ? ORDER BY timemodified DESC";
        } else {
            $params = array($programmingid);
            $sql = "SELECT id, programmingid, userid FROM 
            {programming_submits} 
            WHERE programmingid = ? ORDER BY userid, timemodified DESC";
        }
    } else {
        $params = null;
        $sql = "SELECT id, programmingid, userid FROM 
        {programming_submits} 
        ORDER BY programmingid, userid, timemodified DESC";
    }
    $submits = $DB->get_records_sql($sql, $params);
    if (is_array($submits)) {
        $ids = array();
        $pprogramming = -1;
        $puser = -1;
        foreach ($submits as $row) {
            if ($row->programmingid != $pprogramming) {
                $puser = -1;
            }
            if ($row->userid != $puser) {
                $c = 1;
            } else {
                $c++;
            }
            if ($c > PROGRAMMING_MAX_ATTEMPTS) {
                $ids[] = $row->id;
            }
            $puser = $row->userid;
            $pprogramming = $row->programmingid;
        }
        $ids = implode(',', $ids);

        if ($ids) {
            #delete from submits table
            $DB->execute("DELETE FROM {programming_submits} WHERE id IN ({$ids})");


            #delete from test results table
            $DB->execute("DELETE FROM {programming_test_results} WHERE submitid IN ({$ids})");
        }
    }

    return true;
}

function programming_get_submit_status_short($submit) {
    switch ($submit->status) {
        case PROGRAMMING_STATUS_NEW:
            return get_string('statusshortnew', 'programming');
        case PROGRAMMING_STATUS_COMPILING:
            return get_string('statusshortcompiling', 'programming');
        case PROGRAMMING_STATUS_COMPILEOK:
            return get_string('statusshortcompileok', 'programming');
        case PROGRAMMING_STATUS_RUNNING:
            return get_string('statusshortrunning', 'programming');
        case PROGRAMMING_STATUS_FINISH:
            return get_string('statusshortfinish', 'programming');
        case PROGRAMMING_STATUS_COMPILEFAIL:
            return get_string('statusshortcompilefail', 'programming');
    }
    return '';
}

function programming_get_submit_status_desc($submit) {
    switch ($submit->status) {
        case PROGRAMMING_STATUS_NEW:
            return get_string('statusnew', 'programming');
        case PROGRAMMING_STATUS_COMPILING:
            return get_string('statuscompiling', 'programming');
        case PROGRAMMING_STATUS_COMPILEOK:
            return get_string('statuscompileok', 'programming');
        case PROGRAMMING_STATUS_RUNNING:
            return get_string('statusrunning', 'programming');
        case PROGRAMMING_STATUS_FINISH:
            return get_string('statusfinish', 'programming');
        case PROGRAMMING_STATUS_COMPILEFAIL:
            return get_string('statuscompilefail', 'programming');
    }
    return '';
}

function programming_get_test_results_short($submit) {
    global $DB;

    $c = new stdClass();
    $c->total = $DB->count_records('programming_tests', array('programmingid' => $submit->programmingid));
    $c->success = $DB->count_records('programming_test_results', array('submitid' => $submit->id, 'passed' => 1));
    $c->fail = $c->total - $c->success;
    if ($c->total == $c->success) {
        return get_string('passalltests', 'programming', $c);
    } else {
        return get_string('successfailshort', 'programming', $c);
    }
}

function programming_get_test_results_desc($submit, $results) {
    $c = new stdClass();
    $c->success = 0;
    $c->fail = 0;
    if (is_array($results)) {
        foreach ($results as $result) {
            if ($result->passed == 0)
                $c->fail++;
            else
                $c->success++;
        }
    }
    $c->total = $c->success + $c->fail;
    return get_string('successfailcount', 'programming', $c);
}

function programming_format_codesize($size) {
    if ($size < 1000)
        return $size . 'B';
    else
        return round($size / 1000, 2) . 'K';
}

function check_crarr() {
    global $_SERVER;

    $uagent = $_SERVER['HTTP_USER_AGENT'];
    if (!strpos($uagent, 'MSIE')) {
        return '&crarr;';
    } else {
        return '<span style="font-family: Wingdings 3">&crarr;</span>';
    }
}

function programming_format_io($message, $autolastreturn = false) {
    $crarr = check_crarr();
    $sizelimit = 1024;

    $haslastreturn = false;
    if (substr($message, strlen($message) - 1, 1) == "\n") {
        $haslastreturn = true;
        $message = substr($message, 0, strlen($message) - 1);
    }

    $message = str_replace(
            array(' ', "\r", "\n"), array('&nbsp;', '', $crarr . '</span></li><li><span>'), htmlspecialchars(mb_substr($message, 0, $sizelimit, 'UTF-8')));

    if ($haslastreturn || $autolastreturn)
        $message .= $crarr;

    return '<div><ol><li><span>' . $message . '</span></li></ol></div>';
}

function programming_format_compile_message($message) {
    $lines = explode("\n", trim($message));
    $html = '<ol>';
    $t = '';
    foreach ($lines as $line) {
        if (strstr($line, 'warning:'))
            $t = 'warning';
        else if (strstr($line, 'error:'))
            $t = 'error';
        else
            $t = 'normal';

        $html .= "<li class='$t'>";
        $html .= str_replace(' ', '&nbsp;', htmlspecialchars($line));
        $html .= '</li>';
    }
    $html .= '</ol>';
    return $html;
}

function programming_parse_compile_message($message) {
    $r = new stdClass();
    $r->warnings = count(preg_split("/\d: warning: /", $message)) - 1;
    $r->errors = count(preg_split("/\d: error: /", $message)) - 1;
    return $r;
}

function programming_delete_submit($submit) {
    global $CFG, $DB;

    $DB->delete_records('programming_test_results', array('submitid' => $submit->id));
    $DB->delete_records('programming_submits', array('id' => $submit->id));

    # update programming_result table
    $params = array($submit->programmingid, $submit->userid);
    $submits = $DB->get_records_sql("
        SELECT * FROM {programming_submits}
         WHERE programmingid = ?
           AND userid = ?
      ORDER BY timemodified DESC", $params);
    $r = $DB->get_record('programming_result', array('programmingid' => $submit->programmingid, 'userid' => $submit->userid));
    if (is_array($submits) && count($submits)) {
        $s = array_shift($submits);
        $r->latestsubmitid = $s->id;
    } else {
        $r->latestsubmitid = 0;
    }
    $DB->update_record('programming_result', $r);
}

function programming_rejudge($programming, $submitid, $groupid, $ac) {
    global $CFG, $DB;

    if (empty($submitid)) {
        $params = array($programming->id, $programming->id);
        $sql = "SELECT latestsubmitid
                  FROM {programming_result} AS pr,
                       {programming_submits} AS ps
                 WHERE pr.latestsubmitid = ps.id
                   AND pr.programmingid = ?
                   AND ps.programmingid = ?";
        if (!$ac) {
            $sql .= " AND (ps.passed = 0 OR ps.passed IS NULL)";
        }

        $lsids = $DB->get_records_sql($sql, $params);
        foreach ($lsids as $submit) {
            $ids[] = $submit->latestsubmitid;
        }
    } else {
        $ids = $submitid;
    }
    $ids = implode(',', $ids);

    $sql = "submitid IN ($ids)";
    $DB->delete_records_select('programming_test_results', $sql);

    $sql = "UPDATE {programming_submits}
               SET judgeresult=null, timeused=null, memused=null,
                   passed=null, status=0, compilemessage=null
             WHERE id IN ($ids)";
    $DB->execute($sql);

    $sql = "INSERT INTO {programming_testers}
            SELECT id, 0, 3, 0
              FROM {programming_submits}
             WHERE id IN ($ids)
               AND id NOT IN (SELECT submitid FROM {programming_testers})";
    $DB->execute($sql);
}

function newme($params = NULL) {
    $uri = me();
    $append = '';
    $search = array();
    $replace = array();
    if (is_array($params)) {
        foreach ($params as $name => $value) {
            $s = '/(.*)&' . $name . '=[\d\w+%]*(.*)/';
            if (preg_match($s, $uri)) {
                $search[] = $s;
                $replace[] = '\1&' . $name . '=' . htmlspecialchars($value) . '\2';
            } else {
                $append .= '&' . $name . '=' . htmlspecialchars($value);
            }
        }
    }
    return preg_replace($search, $replace, $uri) . $append;
}

function programming_check_privilege($courseid, $groupid) {
    global $USER;
    if (!isteacher($courseid))
        return False;
    if (isteacheredit($courseid) || groupmode($courseid, $cm) == NOGROUPS)
        return True;
    if ($usergrps = groups_get_all_groups($courseid, $USER->id)) {
        foreach ($usergrps as $ug) {
            if ($groupid === $ug->id)
                return True;
        }
    }

    return False;
}

function programming_submit_timeused(&$results) {
    $time = 0;
    foreach ($results as $r) {
        $time = max($time, $r->timeused);
    }
    return $time;
}

function programming_submit_memused(&$results) {
    $mem = 0;
    foreach ($results as $r) {
        $mem = max($mem, $r->memused);
    }
    return $mem;
}

function programming_judgeresult_options($addempty = false) {
    $rst = array('AC', 'PE', 'WA', 'RE', 'FPE', 'KS', 'OLE', 'MLE', 'TLE', 'RFC', 'JGE', 'JSE');
    $ret = array();
    if ($addempty)
        $ret[''] = get_string('all');
    foreach ($rst as $k) {
        $ret[$k] = get_string($k, 'programming');
    }
    return $ret;
}

function programming_submit_judgeresult(&$results) {
    $err = array('JSE' => 20, 'JGE' => 19, 'RFC' => 18,
        'TLE' => 10, 'MLE' => 9, 'OLE' => 8,
        'KS' => 14, 'FPE' => 13, 'RE' => 12, 'WA' => 11,
        'PE' => 1, 'AC' => 0);

    $c = -1;
    $errstr = null;
    foreach ($results as $result) {
        if (isset($err[$result->judgeresult]) && $err[$result->judgeresult] > $c) {
            $c = $err[$result->judgeresult];
            $errstr = $result->judgeresult;
        }
    }
    return $errstr;
}

function programming_contest_get_judgeresult(&$results) {
    $errstr = programming_submit_judgeresult($results);
    return get_string($errstr, 'programming');
}

function programming_get_judgeresult($result) {
    if ($result->judgeresult) {
        return '<a title="' . get_string($result->judgeresult . ':description', 'programming') . '">' . get_string($result->judgeresult, 'programming') . '</a>';
    } else {
        return programming_get_fail_reason($result);
    }
}

function programming_get_fail_reason($result, $showmode = PROGRAMMING_SHOWMODE_NORMAL) {
    if ($result->passed)
        return get_string('n/a', 'programming');

    switch ($result->signal) {
        case 0:
            break;
        case 8:
            if ($showmode == PROGRAMMING_SHOWMODE_NORMAL)
                return get_string('failbecausefpe', 'programming');
            else
                return get_string('runtimeerror', 'programming');
        case 15:
            if ($showmode == PROGRAMMING_SHOWMODE_NORMAL)
                return get_string('failbecausetimelimit', 'programming');
            else
                return get_string('timelimitexceed', 'programming');
        case 11:
            if ($showmode == PROGRAMMING_SHOWMODE_NORMAL)
                return get_string('failbecausesegv', 'programming');
            else
                return get_string('runtimeerror', 'programming');
        case 9:
        case 24:
            if ($showmode == PROGRAMMING_SHOWMODE_NORMAL)
                return get_string('failbecausecputimelimit', 'programming');
            else
                return get_string('timelimitexceed', 'programming');
        default:
            if ($showmode == PROGRAMMING_SHOWMODE_NORMAL)
                return get_string('failbecauseunknownsig', 'programming', $result->signal);
            else
                return get_string('runtimeerror', 'programming');
    }

    if ($showmode == PROGRAMMING_SHOWMODE_NORMAL) {
        switch ($result->exitcode) {
            case 125:
                return get_string('failbecausejudgescript', 'programming');
            case 126:
                return get_string('failbecauserestrict', 'programming');
            case 127:
                return get_string('failbecausesimpleguard', 'programming');
            default:
                return get_string('failbecausewrongresult', 'programming');
        }
    } else {
        switch ($result->exitcode) {
            case 125:
            case 126:
            case 127:
                return get_string('runtimeerror', 'programming');
            default:
                return get_string('wronganswer', 'programming');
        }
    }
}

function programming_format_timelimit($timelimit) {
    if ($timelimit) {
        return get_string('nseconds', 'programming', $timelimit);
    } else {
        return get_string('timelimitunlimited', 'programming');
    }
}

function programming_format_memlimit($memlimit) {
    if ($memlimit) {
        return get_string('nkb', 'programming', $memlimit);
    } else {
        return get_string('memlimitunlimited', 'programming');
    }
}

function programming_judge_status($courseid, &$totalcount, $offset = 0, $limit = 15) {
    global $DB;

    if (!isset($courseid))
        return False;

    $crit = $courseid != 1 ? "p.course = $courseid AND" : '';
    $sql = "SELECT ps.id AS psid,
                   ps.userid AS userid,
                   p.globalid,
                   p.name AS pname,
                   ps.timemodified as timemodified,
                   p.id AS pid,
                   ps.status as status
              FROM {programming} AS p,
                   {programming_submits} AS ps
             WHERE $crit p.id = ps.programmingid
          ORDER BY ps.timemodified DESC";
    $rows = $DB->get_records_sql($sql, null, $offset, $limit);

    if ($courseid == 1) {
        $sql = "SELECT COUNT(*) AS total
                  FROM {programming_submits} AS ps";
    } else {
        $sql = "SELECT COUNT(*) AS total
                  FROM {programming} AS p,
                       {programming_submits} AS ps
                 WHERE p.course = $courseid
                   AND ps.programmingid = p.id";
    }
    $totalcount = $DB->count_records_sql($sql);

    if ($rows) {
        foreach ($rows as $row) {
            $row->user = $DB->get_record('user', array('id' => $row->userid));
            switch ($row->status) {
                case PROGRAMMING_STATUS_COMPILEFAIL:
                    $row->judgeresult = get_string('CE', 'programming');
                    break;
                case PROGRAMMING_STATUS_FINISH:
                    $tr = $DB->get_records('programming_test_results', array('submitid' => $row->psid));
                    $row->judgeresult = '';
                    if ($tr) {
                        $row->judgeresult = programming_contest_get_judgeresult($tr);
                        $row->timeused = 0;
                        foreach ($tr as $r) {
                            $row->timeused = max($row->timeused, $r->timeused);
                        }
                        $row->memused = 'Unknown';
                    }
                    break;
                default:
                    $row->judgeresult = get_string('statusshortnew', 'programming');
            }
        }
    }

    return $rows;
}

function programming_latest_ac($courseid, $roleid, &$totalcount, $offset = 0, $limit = 15) {
    global $DB;

    if (!isset($courseid))
        return False;
    $context = context_module::instance($courseid);

    if ($courseid == 1) {
        $sql = "SELECT ps.id AS psid,
                       ps.userid AS userid,
                       p.name AS pname,
                       p.globalid,
                       ps.timemodified as timemodified,
                       p.id AS pid
                  FROM {programming} AS p,
                       {programming_result} AS pr,
                       {programming_submits} AS ps
                 WHERE pr.programmingid = p.id
                   AND pr.latestsubmitid = ps.id
                   AND ps.passed = 1
              ORDER BY ps.timemodified DESC";
        $rows = $DB->get_records_sql($sql, null, $offset, $limit);

        $sql = "SELECT COUNT(*) AS total
                  FROM {programming} AS p,
                       {programming_result} AS pr,
                       {programming_submits} AS ps
                 WHERE pr.programmingid = p.id
                   AND pr.latestsubmitid = ps.id
                   AND ps.passed = 1";
        $totalcount = $DB->count_records_sql($sql);
    } else {
        $params = array($courseid, $roleid, $context->id);

        $sql = "SELECT ps.id AS psid,
                       ps.userid AS userid,
                       p.name AS pname,
                       p.globalid,
                       ps.timemodified as timemodified,
                       p.id AS pid
                  FROM {programming} AS p,
                       {programming_result} AS pr,
                       {programming_submits} AS ps,
                       {role_assignments} AS ra
                 WHERE p.course = ?
                   AND ra.roleid = ?
                   AND ra.contextid = ?
                   AND pr.programmingid = p.id
                   AND pr.latestsubmitid = ps.id
                   AND ps.passed = 1
                   AND pr.userid = ra.userid
              ORDER BY ps.timemodified DESC";
        $rows = $DB->get_records_sql($sql, $params, $offset, $limit);
        $params = array($courseid, $roleid, $context->id);

        $sql = "SELECT COUNT(*) AS total
                  FROM {programming} AS p,
                       {programming_result} AS pr,
                       {programming_submits} AS ps,
                       {role_assignments} AS ra
                 WHERE p.course = ?
                   AND ra.roleid = ?
                   AND ra.contextid = ?
                   AND pr.programmingid = p.id
                   AND pr.latestsubmitid = ps.id
                   AND ps.passed = 1
                   AND pr.userid = ra.userid";
        $totalcount = $DB->count_records_sql($sql, $params);
    }

    if ($rows) {
        foreach ($rows as $row) {
            $row->user = $DB->get_record('user', array('id' => $row->userid));
        }
    }

    return $rows;
}

/**
 * Calculate the standing of the contest.
 *
 *  1. Each run is either accepted or rejected.
 *  2. The problem is considered solved by the team, if one of the runs
 *     submitted for it is accepted.
 *  3. The time consumed for a solved problem is the time elapsed from
 *     the beginning of the contest to the submission of the first accepted
 *     run for this problem (in minutes) plus 20 minutes for every other run
 *     for this problem before the accepted one. For an unsolved problem
 *     consumed time is not computed.
 *  4. The total time is the sum of the time consumed for each problem solved.
 *  5. Teams are ranked according to the number of solved problems. Teams that
 *     solve the same number of problems are ranked by the least total time.
 *  6. While the time shown is in minutes, the actual time is measured to the
 *     precision of 1 second, and the the seconds are taken into account when
 *     ranking teams.
 *  7. Teams with equal rank according to the above rules must be sorted by
 *     increasing team number. 
 *
 * @param courseid In which course the contest is hold.
 * @param wrongsubmitminutes How many minutes for wrong submit.
 * @param roleid Which role is included
 * @return An array contains all the information of teams.
 */
function programming_calc_standing($courseid, $roleid, $wrongsubmitminutes = 20, $offset = 0, $limit = 10) {
    global $DB;

    if (!isset($courseid))
        return array();
    if ($courseid == 1) {
        $query = "SELECT pr.userid,
                         COUNT(*) AS ac
                    FROM {programming_result} AS pr,
                         {programming_submits} AS ps
                   WHERE pr.latestsubmitid = ps.id
                     AND ps.passed = 1
                GROUP BY pr.userid
                ORDER BY ac DESC";
    } else {
        $context = context_course::instance($courseid);
        $wss = $wrongsubmitminutes * 60;

        $query = "SELECT pr.userid,
                   SUM(ps.passed) AS ac,
                   SUM((ps.timemodified-p.timeopen)*ps.passed) AS timeused,
                   SUM(pr.submitcount * ps.passed) AS submitcount,
                   SUM(((ps.timemodified - p.timeopen) +
                       (pr.submitcount - ps.passed)*{$wss}) * ps.passed) AS penalty
              FROM {programming} AS p,
                   {programming_result} AS pr,
                   {programming_submits} AS ps,
                   {role_assignments} AS ra
             WHERE p.course={$courseid}
               AND ra.roleid = {$roleid}
               AND ra.contextid = {$context->id}
               AND ps.programmingid = p.id
               AND pr.programmingid = p.id
               AND pr.userid = ra.userid
               AND pr.latestsubmitid = ps.id
          GROUP BY pr.userid
            HAVING ac > 0
          ORDER BY ac DESC, penalty ASC";
    }

    //echo $query;

    $standing = $DB->get_records_sql($query, null, $offset, $limit);
    if (!is_array($standing))
        $standing = array();
    foreach ($standing as $row) {
        $row->user = $DB->get_record('user', array('id' => $row->userid));
    }

    return $standing;
}

function programming_count_standing($courseid, $roleid) {
    global $DB;

    if (!isset($courseid))
        return 0;
    if ($courseid == 1) {
        $query = "SELECT COUNT(*) AS COUNT FROM (
                  SELECT DISTINCT pr.userid
                    FROM {programming_result} AS pr,
                         {programming_submits} AS ps
                   WHERE pr.latestsubmitid = ps.id
                     AND ps.passed = 1) AS c";
    } else {
        $context = context_course::instance($courseid);

        $query = "SELECT COUNT(*) AS count FROM (
                SELECT DISTINCT pr.userid
                  FROM {programming} AS p,
                       {programming_result} AS pr,
                       {programming_submits} AS ps,
                       {role_assignments} AS ra
                 WHERE p.course={$courseid}
                   AND ra.roleid = {$roleid}
                   AND ra.contextid = {$context->id}
                   AND ps.programmingid = p.id
                   AND pr.programmingid = p.id
                   AND pr.userid = ra.userid
                   AND pr.latestsubmitid = ps.id
                   AND ps.passed = 1) AS c";
    }
    //echo $query;

    return $DB->count_records_sql($query);
}

function programming_submit_remove_preset($code) {
    $ret = array();
    $s = 0;

    $lines = explode("\n", $code);
    foreach ($lines as $line) {
        $line = trim($line, "\r");
        switch ($s) {
            case 0:
                if ($line == PROGRAMMING_PRESET_BEGIN) {
                    $s = 1;
                }
                if ($s == 0) {
                    $ret[] = $line;
                }
                break;
            case 1:
                if ($line == PROGRAMMING_PRESET_END) {
                    $s = 0;
                }
                break;
        }
    }

    return implode("\n", $ret);
}

/**
 * Put prepend and postpend preset code and user submitted code together.
 */
function programming_format_code($programming, $submit, $check = false) {
    global $DB;
    if (is_object($programming)) {
        $programming = $programming->id;
    }
    $prepend = $DB->get_record('programming_presetcode', array('programmingid' => $programming, 'languageid' => $submit->language, 'name' => '<prepend>'));
    $postpend = $DB->get_record('programming_presetcode', array('programmingid' => $programming, 'languageid' => $submit->language, 'name' => '<postpend>'));

    $ret = array();
    if (!empty($prepend)) {
        $ret[] = PROGRAMMING_PRESET_BEGIN;
        $ret[] = trim(!$check || empty($prepend->presetcodeforcheck) ? $prepend->presetcode : $prepend->presetcodeforcheck);
        $ret[] = PROGRAMMING_PRESET_END;
    }

    if (is_object($submit)) {
        $ret[] = $submit->code;
    }

    if (!empty($postpend)) {
        $ret[] = PROGRAMMING_PRESET_BEGIN;
        $ret[] = trim(!$check || empty($postpend->presetcodeforcheck) ? $postpend->presetcode : $postpend->presetcodeforcheck);
        $ret[] = PROGRAMMING_PRESET_END;
    }
    return implode("\n\n", $ret);
}

function programming_get_presetcode_name($presetcode) {
    switch ($presetcode->name) {
        case '<prepend>':
            return get_string('prependcode', 'programming');
        case '<postpend>':
            return get_string('postpendcode', 'programming');
        default:
            return $presetcode->name;
    }
}

function programming_format_presetcode($presetcode) {
    $ret = array(PROGRAMMING_PRESET_BEGIN, trim($presetcode->presetcode),
        PROGRAMMING_PRESET_END);
    return implode("\n\n", $ret);
}

function get_visible_programmings($courseid) {
    $DB;
    $sql = "SELECT p.*, cm.visible
              FROM {course_modules} cm,
                   {programming} p
             WHERE cm.course=$courseid
               AND p.course=$courseid
               AND cm.instance = p.id
          ORDER BY p.name";
    return $DB->get_records_sql($sql);
}

function programming_adjust_sequence(&$records, $moveid, $direction) {
    $seq = array();
    $i = 1;
    $idx = 0;
    foreach ($records as $rec) {
        if ($moveid == $rec->id)
            $idx = $i;
        $seq[$i++] = $rec;
    }

    if ($idx) {
        if ($direction == 1 && $idx > 1) { // move up
            $t = $seq[$idx];
            $seq[$idx] = $seq[$idx - 1];
            $seq[$idx - 1] = $t;
        } else if ($direction == 2 && $idx < count($records)) {
            $t = $seq[$idx];
            $seq[$idx] = $seq[$idx + 1];
            $seq[$idx + 1] = $t;
        }
    }
    return $seq;
}

function programming_presetcode_adjust_sequence($programmingid, $moveid = 0, $direction = 0) {
    global $DB;
    $codes = $DB->get_records('programming_presetcode', array('programmingid' => $programmingid, 'sequence' => 'id, sequence'));
    if (!is_array($codes))
        return;

    $seq = programming_adjust_sequence($codes, $moveid, $direction);

    foreach ($seq as $i => $code) {
        if ($code->sequence != $i) {
            $code->sequence = $i;
            $DB->update_record('programming_presetcode', $code);
        }
    }
}

function programming_datafile_adjust_sequence($programmingid, $moveid = 0, $direction = 0) {
    global $DB;
    $codes = $DB->get_records('programming_datafile', array('programmingid' => $programmingid, 'seq' => 'id, seq'));
    if (!is_array($codes))
        return;

    $seq = programming_adjust_sequence($codes, $moveid, $direction);

    foreach ($seq as $i => $code) {
        if ($code->seq != $i) {
            $code->seq = $i;
            $DB->update_record('programming_datafile', $code);
        }
    }
}

function programming_testcase_adjust_sequence($programmingid, $moveid = 0, $direction = 0) {
    global $DB;
    $codes = $DB->get_records('programming_tests', array('programmingid' => $programmingid, 'seq' => 'id, seq'));
    if (!is_array($codes))
        return;

    $seq = programming_adjust_sequence($codes, $moveid, $direction);

    foreach ($seq as $i => $code) {
        if ($code->seq != $i) {
            $code->seq = $i;
            $DB->update_record('programming_tests', $code);
        }
    }
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function programming_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS: return true;
        case FEATURE_GROUPINGS: return true;
        case FEATURE_GROUPMEMBERSONLY: return true;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE: return true;
        case FEATURE_GRADE_OUTCOMES: return true;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_SHOW_DESCRIPTION: return true;
        case FEATURE_ADVANCED_GRADING: return true;

        default: return null;
    }
}

/**
 * This fucntion extends the global navigation for the site.
 * It is important to note that you should not rely on PAGE objects within this
 * body of code as there is no guarantee that during an AJAX request they are
 * available
 *
 * @param navigation_node $navigation The programming node within the global navigation
 * @param object $course The course object returned from the DB
 * @param object $module The module object returned from the DB
 * @param object $cm The course module instance returned from the DB
 */
function programming_extend_navigation($navigation, $course, $module, $cm) {
    global $CFG, $USER, $PAGE, $OUTPUT;

    $currentgroup = groups_get_activity_group($cm, true);

    $target = $CFG->wwwroot . '/mod/programming/';
    $param = array('id' => $cm->id);

    $navigation->add(get_string('view', 'programming'), new moodle_url($target . 'view.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
    $navigation->add(get_string('submit', 'programming'), new moodle_url($target . 'submit.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
    $navigation->add(get_string('result', 'programming'), new moodle_url($target . 'result.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
    $navigation->add(get_string('submithistory', 'programming'), new moodle_url($target . 'history.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));

    $context = context_module::instance($cm->id);

    if (has_capability('mod/programming:viewreport', $context)) {
        $reportnode = $navigation->add(get_string('reports', 'programming'), new moodle_url($target . 'reports/summary.php', $param), navigation_node::TYPE_CONTAINER, null, null, new pix_icon('c/group', ''));
        $reportnode->add(get_string('summary', 'programming'), new moodle_url($target . 'reports/summary.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
        $reportnode->add(get_string('detail', 'programming'), new moodle_url($target . 'reports/detail.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
        $reportnode->add(get_string('bestprograms', 'programming'), new moodle_url($target . 'reports/best.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
        $reportnode->add(get_string('judgeresultcountchart', 'programming'), new moodle_url($target . 'reports/judgeresultchart.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
    }

    if (has_capability('mod/programming:viewresemble', $context)) {
        $navigation->add(get_string('resemble', 'programming'), new moodle_url($target . 'resemble/view.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
    }
}

function programming_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $navigation) {
    global $USER, $PAGE, $CFG, $DB, $OUTPUT;

    $target = $CFG->wwwroot . '/mod/programming/';
    $param = array('id' => $PAGE->cm->id);

    $navigation->add(get_string('view', 'programming'), new moodle_url($target . 'view.php', $param), navigation_node::TYPE_USER, null, null, new pix_icon('c/group', ''));
}

function programming_navtab($currenttab, $currenttab2, $course, $module, $cm) {
    global $CFG;
    $context = context_module::instance($cm->id);
    $tabs = array();
    $inactive = NULL;

    $row = array();

    $row[] = new tabobject('view', $CFG->wwwroot . '/mod/programming/view.php?id=' . $cm->id, get_string('view', 'programming'), '', true);

    $row[] = new tabobject('submit', $CFG->wwwroot . '/mod/programming/submit.php?id=' . $cm->id, get_string('submit', 'programming'), '', true);

    $row[] = new tabobject('result', $CFG->wwwroot . '/mod/programming/result.php?id=' . $cm->id, get_string('result', 'programming'), '', true);

    $row[] = new tabobject('history', $CFG->wwwroot . '/mod/programming/history.php?id=' . $cm->id, get_string('submithistory', 'programming'), '', true);

    if (has_capability('mod/programming:edittestcase', $context)) {
        $row[] = new tabobject('edittest', $CFG->wwwroot . '/mod/programming/testcase/list.php?id=' . $cm->id, get_string('testenv', 'programming'));
    }
    if (has_capability('mod/programming:viewreport', $context)) {
        $row[] = new tabobject('reports', $CFG->wwwroot . '/mod/programming/reports/summary.php?id=' . $cm->id, get_string('reports', 'programming'), '', true);
    }
    if (has_capability('mod/programming:viewresemble', $context)) {
        $row[] = new tabobject('resemble', $CFG->wwwroot . '/mod/programming/resemble/view.php?id=' . $cm->id, get_string('resemble', 'programming'), '', true);
    }

    $tabs[] = $row;

    if ($currenttab == 'edittest' && has_capability('mod/programming:edittestcase', $context)) {
        $row = array();
        $inactive[] = 'edittest';
        $row[] = new tabobject('testcase', $CFG->wwwroot . '/mod/programming/testcase/list.php?id=' . $cm->id, get_string('testcase', 'programming'));
        $row[] = new tabobject('datafile', $CFG->wwwroot . '/mod/programming/datafile/list.php?id=' . $cm->id, get_string('datafile', 'programming'));
        $row[] = new tabobject('presetcode', $CFG->wwwroot . '/mod/programming/presetcode/list.php?id=' . $cm->id, get_string('presetcode', 'programming'));
        $row[] = new tabobject('validator', $CFG->wwwroot . '/mod/programming/validator/edit.php?id=' . $cm->id, get_string('validator', 'programming'));

        $tabs[] = $row;
    }

    if ($currenttab == 'reports') {
        $row = array();
        $inactive[] = 'reports';
        $row[] = new tabobject('reports-summary', $CFG->wwwroot . '/mod/programming/reports/summary.php?id=' . $cm->id, get_string('summary', 'programming'));
        $row[] = new tabobject('reports-detail', $CFG->wwwroot . '/mod/programming/reports/detail.php?id=' . $cm->id . '&amp;latestonly=1', get_string('detail', 'programming'));
        $row[] = new tabobject('reports-best', $CFG->wwwroot . '/mod/programming/reports/best.php?id=' . $cm->id, get_string('bestprograms', 'programming'));
        $row[] = new tabobject('reports-judgeresultchart', $CFG->wwwroot . '/mod/programming/reports/judgeresultchart.php?id=' . $cm->id, get_string('judgeresultcountchart', 'programming'));
        $tabs[] = $row;
    }

    if ($currenttab == 'resemble') {
        $row = array();
        $inactive[] = 'resemble';
        $row[] = new tabobject('resemble-view', $CFG->wwwroot . '/mod/programming/resemble/view.php?id=' . $cm->id, get_string('personal', 'programming'));
        if (has_capability('mod/programming:editresemble', $context)) {
            $row[] = new tabobject('resemble-edit', $CFG->wwwroot . '/mod/programming/resemble/edit.php?id=' . $cm->id, get_string('edit'));
        }
        if (has_capability('mod/programming:updateresemble', $context)) {
            $row[] = new tabobject('resemble-analyze', $CFG->wwwroot . '/mod/programming/resemble/analyze.php?id=' . $cm->id, get_string('analyze', 'programming'));
        }
        $tabs[] = $row;
    }

    /*     * ***************************
     * stolen code from quiz report
     * *************************** */
    if ($currenttab == 'templates' and isset($mode)) {
        $inactive[] = 'templates';
        $templatelist = array('listtemplate', 'singletemplate', 'addtemplate', 'rsstemplate', 'csstemplate');

        $row = array();
        $currenttab = '';
        foreach ($templatelist as $template) {
            $row[] = new tabobject($template, "templates.php?d=$data->id&amp;mode=$template", get_string($template, 'data'));
            if ($template == $mode) {
                $currenttab = $template;
            }
        }
        $tabs[] = $row;
    }

    $tab = new StdClass;
    $tab->tabs = $tabs;
    $tab->currenttab = $currenttab;
    $tab->active = empty($currenttab2) ? null : array($currenttab2);
    $tab->inactive = $inactive;

    return $tab;
}

?>
