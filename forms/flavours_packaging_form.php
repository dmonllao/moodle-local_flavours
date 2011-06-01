<?php 

require_once($CFG->libdir . '/formslib.php');

class flavours_packaging_form extends moodleform {
    
    function definition() {
        
        global $USER;
        
        $mform = & $this->_form;
        
        $mform->addElement('text', 'name', get_string('flavourname', 'local_flavours'));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
        
        $mform->addElement('htmleditor', 'description', get_string('flavourdescription', 'local_flavours'));
        $mform->setType('description', PARAM_CLEANHTML);
        
        $mform->addElement('text', 'author', get_string('author', 'flavourdescription'), 'maxlength="154" size="40"');
        $mform->setType('author', PARAM_TEXT);
        $mform->setDefault('author', $USER->firstname.' '.$USER->lastname);
        
        $mform->addElement('submit', 'admin_presets_submit', get_string('packageflavour', 'local_flavours'));
    }
}
