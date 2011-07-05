<?php 

require_once($CFG->libdir . '/formslib.php');

class flavours_deployment_form extends moodleform {
    
    public function definition() {
        
        $mform = & $this->_form;
        
        // Step header
        $steplabel = get_string('deploymentpreviewheader', 'local_flavours');
        $mform->addElement('header', 'flavourdata', $steplabel);
        
        // Flavour info
        $fields = array('name', 'description', 'author', 'timecreated', 'moodlerelease',
            'moodleversion');
        foreach ($fields as $field) {
            $label = '<strong>'.get_string('flavour' . $field, 'local_flavours').'</strong>';
            $mform->addElement('static', $field, $label);
        }
        
        // Ingredients
        $mform->addElement('header', 'ingredients', 
            get_string('selectingredients', 'local_flavours'));
        $mform->addElement('html', '<div id="id_ingredients_tree" class="ygtv-checkbox">'.
            $this->_customdata["treedata"].'</div>');
    
        // Alerts
        // TODO: Display the array filled in flavours_deployment
        // TODO: Manage hide/show with JS 

        $mform->addElement('hidden', 'action', 'deployment_execute');
        $mform->addElement('submit', 'ingredients_submit', 
            get_string('deployflavour', 'local_flavours'));
    }
}
