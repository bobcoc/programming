<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_programming_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $COURSE;
        $mform    =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'globalid', get_string('globalid', 'programming'));
        $mform->setType('globalid', PARAM_TEXT);

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'30'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements(get_string('programmingintro', 'programming'));

        $mform->addElement('header', '', get_string('grade', 'mod_programming'));

        $options = array();
        $options[0] = get_string('nograde', 'programming');
        for ($i = 5; $i <= 100; $i += 5) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'grade', get_string('grade', 'mod_programming'), $options);

        $options = array();
        for ($i = 10; $i > 0; $i -= 1) {
            $options[$i] = $i / 10.0;
        }
        $mform->addElement('select', 'discount', get_string('discount', 'programming'), $options);

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'programming'));
        $mform->addElement('date_time_selector', 'timediscount', get_string('timediscount', 'programming'));
        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'programming'));

        $mform->addElement('selectyesno', 'allowlate', get_string('allowlate', 'programming'));
    
//-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('program', 'programming'));

        $langs = programming_get_language_options();
        $select = $mform->addElement('select', 'langlimit', get_string('langlimit', 'programming'), $langs);
        $select->setMultiple(true);
        $cats = programming_get_category_options();
        $select1 = $mform->addElement('select', 'category', get_string('category', 'programming'), $cats);
        $select1->setMultiple(true);
        $options = programming_get_difficulty_options();
        $mform->addElement('select', 'diffculty', get_string('difficulty', 'programming'), $options);        
        $inputs = array();
        $inputs[] = $mform->createElement('radio', 'inputs', null, get_string('stdin', 'programming'), 0);
        $inputs[] = $mform->createElement('radio', 'inputs', null, get_string('inputfromfile', 'programming'), 1);
        $inputs[] = $mform->createElement('text', 'inputfile');
        $mform->setType('inputfile', PARAM_TEXT);
        $mform->addGroup($inputs, 'inputs', get_string('inputfile', 'programming'), ' ', false);
        $mform->disabledIf('inputfile', 'inputs', 'eq', 0);

        $outputs = array();
        $outputs[] = $mform->createElement('radio', 'outputs', null, get_string('stdout', 'programming'), 0);
        $outputs[] = $mform->createElement('radio', 'outputs', null, get_string('outputtofile', 'programming'), 1);
        $outputs[] = $mform->createElement('text', 'outputfile');
        $mform->setType('outputfile', PARAM_TEXT);
        $mform->addGroup($outputs, 'outputs', get_string('outputfile', 'programming'), ' ', false);
        $mform->disabledIf('outputfile', 'outputs', 'eq', 0);

        $options = programming_get_timelimit_options();
        $mform->addElement('select', 'timelimit', get_string('timelimit', 'programming'), $options);

        $options = programming_get_memlimit_options();
        $mform->addElement('select', 'memlimit', get_string('memlimit', 'programming'), $options);

        $options = programming_get_nproc_options();
        $mform->addElement('select', 'nproc', get_string('extraproc', 'programming'), $options);

        $options = array();
        $options[0] = get_string('attemptsunlimited', 'programming');
        $options[1] = get_string("oneattempt", 'programming');
        for ($i = 2; $i <= PROGRAMMING_MAX_ATTEMPTS; $i++) {
            $options[$i] = get_string('nattempts', 'programming', $i);
        }
        $mform->addElement('select', 'attempts', get_string('attempts', 'programming'), $options);

        $mform->addElement('selectyesno', 'keeplatestonly', get_string('keeplatestonly', 'programming'));

        $options = programming_get_showmode_options();
        $mform->addElement('select', 'showmode', get_string('showmode', 'programming'), $options);
        
//-------------------------------------------------------------------------------
        $features = new stdClass;
        $features->groups = true;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);
//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        if (empty($default_values['discount'])) {
            $default_values['discount'] = 8;
        }
        if (empty($default_values['allowlate'])) {
            $default_values['allowlate'] = 1;
        }
        if (empty($default_values['timelimit'])) {
            $default_values['timelimit'] = 1;
        }
//
        if (empty($default_values['inputs'])) {
            $default_values['inputs'] = (isset($default_values['inputfile']) && $default_values['inputfile']) ? 1 : 0;
        }

        if (empty($default_values['outputs'])) {
            $default_values['outputs'] = (isset($default_values['outputfile']) && $default_values['outputfile']) ? 1 : 0;
        }

        if (empty($default_values['langlimit']) && !empty($default_values['id'])) {
            $default_values['langlimit'] = array();
            global $DB;
            $rows = $DB->get_records('programming_langlimit',array('programmingid'=>$default_values['id']));
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $default_values['langlimit'][] = $row->languageid;
                }
            }
        }
        if (empty($default_values['category']) && !empty($default_values['id'])) {
            $default_values['category'] = array();
            global $DB;
            $rows1 = $DB->get_records('programming_catproblemmap',array('pid'=>$default_values['id']));
            if (is_array($rows1)) {
                foreach ($rows1 as $row1) {
                    $default_values['category'][] = $row1->catid;
                }
            }
        }
        
    }
}
?>
