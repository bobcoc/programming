<?php

require_once('../../../config.php');
require_once('../lib.php');
require_once("../../../lib/filelib.php");
require_once('resemble_analyze_lib.php');

$id = required_param('id', PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);

$action = optional_param('action', 0, PARAM_CLEAN);
$max = optional_param('max', 0, PARAM_INT);
$lowest = optional_param('lowest', 0, PARAM_INT);

$params = array('id' => $id, 'group' => $group, 'action' => $action, 'max' => $max, 'lowest' => $lowest);
$PAGE->set_url('/mod/programming/resemble/analyze.php', $params);

if (!$cm = get_coursemodule_from_id('programming', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

if (!$programming = $DB->get_record('programming', array('id' => $cm->instance))) {
    print_error('invalidprogrammingid', 'programming');
}

require_login($course->id, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/programming:updateresemble', $context);


/// Print the page header
$PAGE->set_title($programming->name);
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();

/// Print tabs
$renderer = $PAGE->get_renderer('mod_programming');
$tabs = programming_navtab('resemble', 'resemble-analyze', $course, $programming, $cm);
echo $renderer->render_navtab($tabs);

/// Print page content

if ($action) {
    if ($group != 0) {
        $users = get_group_users($group);
    } else {
        if ($usergrps = groups_get_all_groups($course->id, $USER->id)) {
            foreach ($usergrps as $ug) {
                $users = array_merge($users, get_group_users($ug->id));
            }
        } else {
            $users = False;
        }
    }

    $sql = "SELECT * FROM {programming_submits} WHERE programmingid={$programming->id}";
    if (is_array($users)) {
        $sql .= ' AND userid IN (' . implode(',', array_keys($users)) . ')';
    }
    $sql .= ' ORDER BY timemodified DESC';
    $submits = $DB->get_records_sql($sql);

    $users = array();
    $latestsubmits = array();
    if (is_array($submits)) {
        foreach ($submits as $submit) {
            if (in_array($submit->userid, $users))
                continue;
            $users[] = $submit->userid;
            $latestsubmits[] = $submit;
        }
    }
    $sql = 'SELECT * FROM {user} WHERE id IN (' . implode(',', $users) . ')';
    $users = $DB->get_records_sql($sql);

    // create dir
    $dirname = $CFG->dataroot . '/temp';
    if (!file_exists($dirname)) {
        mkdir($dirname, 0777) or ( 'Failed to create dir');
    }
    $dirname .= '/programming';
    if (!file_exists($dirname)) {
        mkdir($dirname, 0777) or ( 'Failed to create dir');
    }
    $dirname .= '/' . $programming->id;
    if (file_exists($dirname)) {
        if (is_dir($dirname)) {
            fulldelete($dirname) or error('Failed to remove dir contents');
            //rmdir($dirname) or error('Failed to remove dir');
        } else {
            unlink($dirname) or error('Failed to delete file');
        }
    }
    mkdir($dirname, 0700) or error('Failed to create dir');

    $files = array();
    // write files
    $exts = array('.txt', '.c', '.cxx', '.java', '.java', '.pas', '.py', '.cs');
    foreach ($latestsubmits as $submit) {
        $ext = $exts[$submit->language];
        $filename = "{$dirname}/{$submit->userid}-{$submit->id}{$ext}";
        $files[] = $filename;
        $f = fopen($filename, 'w');
        fwrite($f, $submit->code);
        fwrite($f, "\r\n");
        fclose($f);
    }
    //echo "dir is $dirname <br />";

    $cwd = getcwd();
    chdir($dirname);
    $url = array();
    exec("perl $cwd/moss.pl -u {$CFG->programming_moss_userid} *", $url);
    print_r($url);
    $url = $url[count($url) - 1];
    echo "See result $url <br />";

    // remove temp
    fulldelete($dirname);

    parse_result($programming->id, $url, $max, $lowest);
} else {
    include_once('resemble_analyze.tpl.php');
}

/// Finish the page
$OUTPUT->footer($course);
?>
