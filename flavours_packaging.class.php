<?php 

require_once(dirname(__FILE__) . '/flavours.class.php');
require_once(dirname(__FILE__) . '/forms/flavours_packaging_form.php');

class flavours_packaging extends flavours {
    
    // TODO: Allow the ingredients types addition without code edition
    private $ingredienttypes = array('setting', 'plugin', 'lang');
    
    public function packaging_form() {
        
        global $CFG, $PAGE;
        
        // Getting the ingredient types data
        foreach ($this->ingredienttypes as $type) {
            
            // Ingredient type
            $classname = 'flavours_ingredient_'.$type;
            $filepath = dirname(__FILE__) . '/ingredient/'.$classname.'.class.php';
            if (!file_exists($filepath)) {
                print_error('ingredienttypenotavailable', 'local_flavours');
            }
            
            // Getting the system ingredients of that type 
            require_once($filepath);
            $this->ingredients[$type] = new $classname();
            $this->ingredients[$type]->get_system_data();
        }

        // Initializing the tree
        $PAGE->requires->js_init_call('M.local_flavours.init', null, true);
        
        // And rendering on dom ready
        $PAGE->requires->js_init_call('M.local_flavours.render', null, true);
        
        
        // Fill the ingredients tree with this->ingredients (ondomready)
        $customdata['treedata'] = $this->get_tree_ingredients();
        
        // Creating the form to display in $this->display()
        $this->form = new flavours_packaging_form($this->url, $customdata);
    }
    
    
    public function packaging_execute() {
        notify(print_r($_REQUEST));
        $this->output = 'I\'ll be a tasty compressed file';
    }
}
