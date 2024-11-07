<?PHP

require_once("../../config.php");
require_once("lib.php");
require_once("../../lib/filelib.php");

$id = required_param('id', PARAM_INT);     // programming ID
$group = optional_param('group', 0, PARAM_INT);

$params = array('id' => $id);
if (!empty($group)) {
    $params['group'] = $group;
}
$PAGE->set_url('/mod/programming/packaing.php', $params);

if (!$cm = get_coursemodule_from_id('programming', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

if (!$programming = $DB->get_record('programming', array('id' => $cm->instance))) {
    print_error('invalidprogrammingid', 'programming');
}

$context = context_module::instance($cm->id);

require_login($course->id, true, $cm);
require_capability('mod/programming:viewotherprogram', $context);


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
    mkdir($dirname, 0777) or ('Failed to create dir');
}
$dirname .= '/programming';
if (!file_exists($dirname)) {
    mkdir($dirname, 0777) or ('Failed to create dir');
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
foreach ($latestsubmits as $submit) {
    if ($submit->language == 1)
        $ext = '.c';
    elseif ($submit->language == 2)
        $ext = '.cpp';
    $filename = $dirname . '/' . $users[$submit->userid]->username . '-' . $submit->id . $ext;
    $files[] = $filename;
    $f = fopen($filename, 'w');
    fwrite($f, $submit->code);
    fwrite($f, "\r\n");
    fclose($f);
}

// zip file
// eli changed ! 2009-8-18 22:37:12
$dest = $CFG->dataroot . '/' . $course->id;
if (!file_exists($dest)) {
    mkdir($dest, 0777) or error('Failed to create dir');
}
$dest .= '/programming-' . $programming->id;

if ($group === 0) {
    $dest .= '-all';
} else {
    $group_obj = get_current_group($course->id, True);
    $dest .= '-' . $group_obj->name;
}
$dest .= '.zip';
if (file_exists($dest)) {
    unlink($dest) or error("Failed to delete dest file");
}
zip_files($files, $dest);

// remove temp
fulldelete($dirname);

$g = $group === 0 ? 'all' : $group_obj->name;
$from_zip_file = $course->id . 'programming-' . $programming->id . '-' . $g . '.zip';
$count = count($files);
$referer = $_SERVER['HTTP_REFERER'];
$fs = get_file_storage();
$ctxid = context_user::instance($USER->id)->id;
$fileinfo = array('contextid' => $ctxid, 'component' => 'user', 'filearea' => 'private',
    'itemid' => 0, 'filepath' => '/', 'filename' => $from_zip_file,
    'timecreated' => time(), 'timemodified' => time(), 'userid' => $USER->id);

// Get file
$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

// Delete it if it exists
if ($file) {
    $file->delete();
}

$file = $fs->create_file_from_pathname($fileinfo, $dest);
/// Print the page header
$urlbase = "$CFG->httpswwwroot/pluginfile.php";
$filelink = $CFG->wwwroot.'/pluginfile.php'.'/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . '/' . $file->get_filename() . '?forcedownload=1';

$PAGE->set_title($programming->name);
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();

/// Print tabs
$renderer = $PAGE->get_renderer('mod_programming');
$tabs = programming_navtab('reports', 'reports-packaging', $course, $programming, $cm);
echo $renderer->render_navtab($tabs);

echo html_writer::tag('h2', $programming->name);
echo html_writer::tag('h3', get_string('packagesuccess', 'programming'));

echo html_writer::tag('p', "<a href='$filelink'>" . get_string('download', 'programming') . '</a>');
echo html_writer::tag('p', "<a href='$referer'>" . get_string('return', 'programming') . '</a>');

/// Finish the page
echo $OUTPUT->footer($course);
