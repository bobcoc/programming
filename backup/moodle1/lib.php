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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 * Based off of a template @ http://docs.moodle.org/dev/Backup_1.9_conversion_for_developers
 *
 * @package    mod
 * @subpackage assignment
 * @copyright  2011 Aparup Banerjee <aparup@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Assignment conversion handler
 */
class moodle1_mod_programming_handler extends moodle1_mod_handler {


    public function get_paths() {
        return array(
            new convert_path(
                'programming', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING',
                array(
                    'renamefields' => array(
                        'description' => 'intro',
                        'descformat' => 'introformat',
                    )
                )                    
                    ),
            new convert_path(
                'programming_langlimits', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/LANGLIMITS'),
            new convert_path(
                'programming_langlimit', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/LANGLIMITS/LANGLIMIT'),
            new convert_path(
                'programming_presetcodes', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/PRESETCODES'),
            new convert_path(
                'programming_presetcode', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/PRESETCODES/PRESETCODE'),
            new convert_path(
                'programming_datafiles', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/DATAFILES'),
            new convert_path(
                'programming_datafile', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/DATAFILES/DATAFILE'),
            new convert_path(
                'programming_testcases', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/TESTCASES'),
            new convert_path(
                'programming_testcase', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/TESTCASES/TESTCASE'),
            new convert_path(
                'programming_submits', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/SUBMITS'),
            new convert_path(
                'programming_submit', '/MOODLE_BACKUP/COURSE/MODULES/MOD/PROGRAMMING/SUBMITS/SUBMIT'),
           // new convert_path('','')  
        );
    }
        public function process_programming($data) {

        // get the course module id and context id
        $instanceid     = $data['id'];
        $cminfo         = $this->get_cminfo($instanceid);
        $this->moduleid = $cminfo['id'];
        $contextid      = $this->converter->get_contextid(CONTEXT_MODULE, $this->moduleid);

        // get a fresh new file manager for this instance
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_programming');

        // convert course files embedded into the intro
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $data['intro'] = moodle1_converter::migrate_referenced_files($data['intro'], $this->fileman);

        // start writing choice.xml
        $this->open_xml_writer("activities/programming_{$this->moduleid}/programming.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $this->moduleid,
            'modulename' => 'programming', 'contextid' => $contextid));
        $this->xmlwriter->begin_tag('programming', array('id' => $instanceid));

        foreach ($data as $field => $value) {
            if ($field <> 'id') {
                $this->xmlwriter->full_tag($field, $value);
            }
        }
        return $data;
    }
    public function on_programming_langlimits_start() {
        $this->xmlwriter->begin_tag('langlimits');
    }
    public function on_programming_langlimits_end() {
        $this->xmlwriter->end_tag('langlimits');
    }    
    public function on_programming_presetcodes_start() {
        $this->xmlwriter->begin_tag('presetcodes');
    }
    public function on_programming_presetcodes_end() {
        $this->xmlwriter->end_tag('presetcodes');
    }    
    public function on_programming_datafiles_start() {
        $this->xmlwriter->begin_tag('datafiles');
    }
    public function on_programming_datafiles_end() {
        $this->xmlwriter->end_tag('datafiles');
    }    
    public function on_programming_testcases_start() {
        $this->xmlwriter->begin_tag('testcases');
    }
    public function on_programming_testcases_end() {
        $this->xmlwriter->end_tag('testcases');
    }        
    public function on_programming_submits_start() {
        $this->xmlwriter->begin_tag('submits');
    }
    public function on_programming_submits_end() {
        $this->xmlwriter->end_tag('submits');
    }       
    
    public function on_programming_end() {
        $this->xmlwriter->end_tag('programming');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();   
    }
    public function process_programming_testcase($data) {
        $this->write_xml('testcase', $data, array('/testcase/id'));
    }
    public function process_programming_datafile($data) {
        $this->write_xml('datafile', $data, array('/datafile/id'));
    }
    public function process_programming_presetcode($data) {
        $this->write_xml('presetcode', $data, array('/presetcode/id'));
    }
    public function process_programming_langlimit($data) {
         $this->write_xml('langlimit', $data, array('/langlimit/id'));
   }    
    public function process_programming_submit($data) {
         $this->write_xml('submit', $data, array('/submit/id'));
   }  
}
