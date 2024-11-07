<?php

require_once('../../../config.php');
require_once('../lib.php');
require_once("../../../lib/filelib.php");
require_once('resemble_analyze_lib.php');

header('Content-Type: text/plain; charset=utf-8');

$id = required_param('id', PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);

$max = optional_param('max', 0, PARAM_INT);
$lowest = optional_param('lowest', 0, PARAM_INT);

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

/// Print page content
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
// echo "dir is $dirname\n";

$cwd = getcwd();
$cmd = "/usr/bin/perl $cwd/moss.pl -u {$CFG->programming_moss_userid} *";
// echo "$cmd\n"; flush();

chdir($dirname);
$ofile = popen($cmd, 'r');
chdir($cwd);

$contents = '';
if ($ofile) {
    while (!feof($ofile)) {
        $read = fread($ofile, 1024);
        $contents .= $read;
        echo $read;
    }
    pclose($ofile);
}
$lastline = substr($contents, strrpos($contents, "\n", -3));

preg_match('/(http:[\.\/a-z0-9]+)/', $lastline, $matches);
$url = $matches[1];
echo "Result: $url\n";

// remove temp
fulldelete($dirname);

parse_result($programming->id, $url, $max, $lowest);
?>
