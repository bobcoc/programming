<?php
require_once ($CFG->libdir.'/formslib.php');

class testcase_form extends moodleform {

    function definition() {
        global $CFG, $COURSE, $OUTPUT, $cm, $programming;
        $mform =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('hidden', 'id', $cm->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'case');
        $mform->setType('case', PARAM_INT);

//        $mform->addElement('textarea', 'input', get_string('input', 'programming').$OUTPUT->help_icon('input', 'programming'), 'rows="2" cols="50"');
        $mform->addElement('filepicker', 'inputfile', get_string('usefile', 'programming'));
//        $mform->addElement('textarea', 'output', get_string('output', 'programming').$OUTPUT->help_icon('output', 'programming'), 'rows="2" cols="50"');
        $mform->addElement('filepicker', 'outputfile', get_string('usefile', 'programming'));

        $mform->addElement('select', 'timelimit', get_string('timelimit', 'programming').$OUTPUT->help_icon('timelimit', 'programming'), programming_get_timelimit_options());
        $mform->setDefault('timelimit', $programming->timelimit);

        $mform->addElement('select', 'memlimit', get_string('memlimit', 'programming').$OUTPUT->help_icon('memlimit', 'programming'), programming_get_memlimit_options());
        $mform->setDefault('memlimit', $programming->memlimit);

        $mform->addElement('select', 'nproc', get_string('extraproc', 'programming').$OUTPUT->help_icon('nproc', 'programming'), programming_get_nproc_options());
        $mform->setDefault('nproc', $programming->nproc);

        $mform->addElement('select', 'weight', get_string('weight', 'programming').$OUTPUT->help_icon('weight', 'programming'), programming_get_weight_options());
        $mform->setDefault('weight', 1);

        $mform->addElement('select', 'pub', get_string('testcasepub', 'programming').$OUTPUT->help_icon('testcasepub', 'programming'), programming_testcase_pub_options());
        $mform->setDefault('pub',-1);

//        $mform->addElement('textarea', 'memo', get_string('memo', 'programming'), 'rows="2" cols="50"');

// buttons
        $this->add_action_buttons();
    }

    function set_data($data) {
        $data->case = $data->id;
        unset($data->id);
        if (strlen($data->input) > 1023) {
            $data->input = '';
        }
        if (strlen($data->output) > 1023) {
            $data->output = '';
        }
        parent::set_data($data);
    }

    /*
    function validation($data, $files) {
        $errors = array();

        if (empty($data['output']) or trim($data['output']) == '')
            if (empty($files['outputfile']))
                $errors['output'] = get_string('required');

        return $errors;
    }*/

}
