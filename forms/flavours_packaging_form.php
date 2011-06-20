<?php 

require_once($CFG->libdir . '/formslib.php');

class flavours_packaging_form extends moodleform {
    
    public function definition() {
        
        global $USER, $OUTPUT;
        
        $mform = & $this->_form;
        
        // General data
        $mform->addElement('header', 'flavourdata', get_string('flavourdata', 'local_flavours'));
        
        $mform->addElement('text', 'name', get_string('flavourname', 'local_flavours'));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
        
        $mform->addElement('htmleditor', 'description', 
            get_string('flavourdescription', 'local_flavours'));
        $mform->setType('description', PARAM_CLEANHTML);
        
        $mform->addElement('text', 'author', get_string('flavourauthor', 'local_flavours'), 
            'maxlength="154" size="40"');
        $mform->setType('author', PARAM_TEXT);
        $mform->setDefault('author', $USER->firstname.' '.$USER->lastname);

        // Ingredients
        $mform->addElement('header', 'ingredients', 
            get_string('selectingredients', 'local_flavours'));
        $mform->addElement('html', '<div id="id_ingredients_tree" class="ygtv-checkbox">'.
            $this->_customdata["treedata"].'</div>');
    
        $mform->addElement('hidden', 'action', 'packaging_execute');
        $mform->addElement('submit', 'ingredients_submit', 
            get_string('packageflavour', 'local_flavours'));
    }
}
