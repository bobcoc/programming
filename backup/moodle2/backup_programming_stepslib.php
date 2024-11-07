<?php

include_once('backup_base64_element.class.php');

/**
 * Define all the backup steps that will be used by the backup_programming_activity_task
 */

/**
 * Define the complete programming structure for backup, with file and id annotations
 */
class backup_programming_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $programming = new backup_nested_element('programming', array('id'), array(
            'name', 'intro', 'introformat', 'grade',
            'globalid', 'timeopen', 'timeclose', 'timelimit', 'memlimit',
            'nproc', 'timediscount', 'discount', 'allowlate', 'attempts',
            'keeplatestonly', 'inputfile', 'outputfile', 'presetcode',
            'generator', 'validator', 'generatortype', 'validatortype',
            'validatorlang', 'showmode', 'timemodified'));

        $languages = new backup_nested_element('langlimits');
        $language = new backup_nested_element('langlimit', array('id'), array('languageid'));

        $testcases = new backup_nested_element('testcases');
        $testcase = new backup_nested_element('testcase', array('id'), array('seq',
            new backup_base64_element('input'), new backup_base64_element('gzinput'),
            new backup_base64_element('output'), new backup_base64_element('gzoutput'),
            'cmdargs', 'timelimit', 'memlimit', 'nproc', 'pub', 'weight', 'memo',
            'timemodified'));

        $datafiles = new backup_nested_element('datafiles');
        $datafile = new backup_nested_element('datafile', array('id'), array(
            'seq', 'filename', 'isbinary', 'datasize', new backup_base64_element('data'),
            'checkdatasize', new backup_base64_element('checkdata'), 'memo', 'timemodified'));

        $presetcodes = new backup_nested_element('presetcodes');
        $presetcode = new backup_nested_element('presetcode', array('id'), array(
            'languageid', 'name', 'sequence', 'presetcode', 'presetcodeforcheck'));

        $submits = new backup_nested_element('submits');
        $submit = new backup_nested_element('submit', array('id'), array(
            'userid', 'timemodified', 'language', 'code',
            'codelines', 'codesize', 'status', 'compilemessage',
            'timeused', 'memused', 'judgeresult', 'passed'));

        $programming->add_child($languages);
        $languages->add_child($language);

        $programming->add_child($testcases);
        $testcases->add_child($testcase);

        $programming->add_child($datafiles);
        $datafiles->add_child($datafile);

        $programming->add_child($presetcodes);
        $presetcodes->add_child($presetcode);

        $programming->add_child($submits);
        $submits->add_child($submit);

        // Define sources
        $programming->set_source_table('programming', array('id' => backup::VAR_ACTIVITYID));
        $language->set_source_table('programming_langlimit', array('programmingid' => backup::VAR_PARENTID));
        $testcase->set_source_table('programming_tests', array('programmingid' => backup::VAR_PARENTID));
        $datafile->set_source_table('programming_datafile', array('programmingid' => backup::VAR_PARENTID));
        $presetcode->set_source_table('programming_presetcode', array('programmingid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $submit->set_source_table('programming_submits', array('programmingid' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $programming->annotate_ids('scale', 'grade');
        $submit->annotate_ids('user', 'userid');

        // Define file annotations
        $programming->annotate_files('mod_programming', 'intro', null); // This file area hasn't itemid

        // Return the root element (programming), wrapped into standard activity structure
        return $this->prepare_activity_structure($programming);
    }
}
