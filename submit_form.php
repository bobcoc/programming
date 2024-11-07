<?php
require_once ($CFG->libdir.'/formslib.php');

class submit_form extends moodleform {

    function definition() {
        global $CFG, $COURSE, $OUTPUT, $cm, $programming;
        global $default_language, $submitfor;
        $mform =& $this->_form;
//-------------------------------------------------------------------------------
        $mform->addElement('hidden', 'id', $cm->id);
	$mform->setType('id', PARAM_INT);
        $mform->addElement('textarea', 'code', get_string('programcode', 'programming'), 'rows="20" cols="90"');
        $attributes = 'onchange ="change()"';
       
        $mform->addElement('select', 'language', get_string('programminglanguage', 'programming'), programming_get_language_options($programming),$attributes);
        $mform->setDefault('language', $default_language);
        $mform->addElement('filepicker', 'sourcefile', get_string('sourcefile', 'programming'), null, array('maxbytes' => 65536));

// buttons
        $this->add_action_buttons();
    }

}
