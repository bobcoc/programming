<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_programming_activity_task
 */

/**
 * Structure step to restore one programming activity
 */
class restore_programming_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $programming = new restore_path_element('programming', '/activity/programming');
        $paths[] = $programming;
        $paths[] = new restore_path_element('programming_langlimit', '/activity/programming/langlimits/langlimit');
        $paths[] = new restore_path_element('programming_presetcode', '/activity/programming/presetcodes/presetcode');
        $paths[] = new restore_path_element('programming_datefile', '/activity/programming/datefiles/datefile');
        $paths[] = new restore_path_element('programming_testcase', '/activity/programming/testcases/testcase');


        if ($userinfo) {
            $paths[] = new restore_path_element('programming_submit', '/activity/programming/submits/submit');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_programming($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        // insert the programming record
        $newitemid = $DB->insert_record('programming', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_programming_submit($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->programmingid = $this->get_new_parentid('programming');

        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('programming_submits', $data);
        $this->set_mapping('programming_submit', $oldid, $newitemid, true); // Going to have files
        //       $this->set_mapping(restore_gradingform_plugin::itemid_mapping('submission'), $oldid, $newitemid);
    }

    protected function process_programming_langlimit($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->programmingid = $this->get_new_parentid('programming');

        $newitemid = $DB->insert_record('programming_langlimit', $data);
        $this->set_mapping('programming_langlimit', $oldid, $newitemid);
    }

    protected function process_programming_presetcode($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->programmingid = $this->get_new_parentid('programming');

        $newitemid = $DB->insert_record('programming_presetcode', $data);
        $this->set_mapping('programming_presetcode', $oldid, $newitemid);
    }

    protected function process_programming_datefile($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->programmingid = $this->get_new_parentid('programming');
        $data->data = base64_decode($data->data);
        $data->checkdata = base64_decode($data->checkdata);

        $newitemid = $DB->insert_record('programming_datefile', $data);
        $this->set_mapping('programming_datefile', $oldid, $newitemid);
    }

    protected function process_programming_testcase($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->programmingid = $this->get_new_parentid('programming');

        $data->input = base64_decode($data->input);
        $data->gzinput = base64_decode($data->gzinput);
        $data->output = base64_decode($data->output);
        $data->gzoutput = base64_decode($data->gzoutput);

        $newitemid = $DB->insert_record('programming_tests', $data);
        $this->set_mapping('programming_testcase', $oldid, $newitemid);
    }

    protected function after_execute() {
        // Add programming related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_programming', 'intro', null);
        // Add programming submission files, matching by programming_submission itemname
    }
}
