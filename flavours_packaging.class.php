<?php 

require_once($CFG->libdir.'/filestorage/zip_packer.php');
require_once($CFG->dirroot.'/backup/util/xml/xml_writer.class.php');
require_once($CFG->dirroot.'/backup/util/xml/output/xml_output.class.php');
require_once($CFG->dirroot.'/backup/util/xml/output/memory_xml_output.class.php');

require_once($CFG->dirroot . '/local/flavours/flavours.class.php');
require_once($CFG->dirroot . '/local/flavours/forms/flavours_packaging_form.php');

class flavours_packaging extends flavours {
    
    // TODO: Allow the ingredients types addition without code edition (opendir ingredient/ maybe)
    private $ingredienttypes = array('setting', 'plugin', 'lang');
    
    
    /**
     * Outputs the packaging form
     */
    public function packaging_form() {
        
        global $CFG, $PAGE;
        
        // Getting the ingredient types data
        foreach ($this->ingredienttypes as $type) {
            
            // instnace_ingredient_type get a new flavours_ingredient_* object
            $this->ingredients[$type] = $this->instance_ingredient_type($type);
            $this->ingredients[$type]->get_system_info();
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
    
    
    /**
     * Packages the entire flavour and returns it
     * @todo Add a checksum
     * @todo Wrap tags data with CDATA
     */
    public function packaging_execute() {
        
        global $USER, $CFG;
        
        $errorredirect = $this->url . '?sesskey=' . sesskey();
        
        // Getting selected data
        $selectedingredients = $this->get_ingredients_from_form();
        if (!$selectedingredients) {
            redirect($errorredirect, get_string('nothingselected', 'local_flavours'), 2);
        }

        // Flavour data
        $form = new flavours_packaging_form($this->url);
        if (!$data = $form->get_data()) {
            print_error('errorpackaging', 'local_flavours');
        }
            
        // Starting <xml>
        // TODO: Replace for file_xml_output()
        $xmloutput = new memory_xml_output();
        $xmlwriter = new xml_writer($xmloutput);
        $xmlwriter->start();
        $xmlwriter->begin_tag('flavour');
        $xmlwriter->full_tag('name', $data->name);
        $xmlwriter->full_tag('description', $data->description);
        $xmlwriter->full_tag('author', $data->author);
        $xmlwriter->full_tag('timecreated', time());
        $xmlwriter->full_tag('moodlerelease', $CFG->release);
        $xmlwriter->full_tag('moodleversion', $CFG->version);
            
        // Random code to store the flavour data
        $hash = sha1('flavour_'.$USER->id.'_'.time());
        $flavourpath = $this->flavourstmpfolder.'/'.$hash;

        if (file_exists($flavourpath) || !mkdir($flavourpath, $CFG->directorypermissions)) {
            print_error('errorpackaging', 'local_flavours');
        }

        // Adding the selected ingredients data
        $xmlwriter->begin_tag('ingredient');
        foreach ($selectedingredients as $ingredienttype => $ingredientsdata) {
                
            // instance_ingredient_type gets a new flavours_ingredient_* object
            $type = $this->instance_ingredient_type($ingredienttype);

            $xmlwriter->begin_tag($type->id);
                
            // It executes the ingredient type specific actions to package
            $type->package_ingredients($xmlwriter, $flavourpath, $ingredientsdata);
                
            $xmlwriter->end_tag($type->id);
        }
        $xmlwriter->end_tag('ingredient');

        // Finishing flavour index
        $xmlwriter->end_tag('flavour');
        $xmlwriter->stop();
        $flavourxml = $xmloutput->get_allcontents();
            
        // Creating the .xml with the flavour info
        $xmlfilepath = $flavourpath . '/flavour.xml';
        if (!$xmlfh = fopen($xmlfilepath, 'w')) {
            print_error('errorpackaging', 'local_flavours');
        }
        fwrite($xmlfh, $flavourxml);
        fclose($xmlfh);
            
        // Flavour contents compression
        $packer = new zip_packer();
        $zipfilepath = $this->flavourstmpfolder . 
            '/' . $hash . '/flavour_' . date('Y-m-d') . '.zip';
        if (!$packer->archive_to_pathname(array('flavour' => $flavourpath), $zipfilepath)) {
            print_error('errorpackaging', 'local_flavours');
        }
            
        session_get_instance()->write_close();
        send_file($zipfilepath, basename($zipfilepath));
            
        // TODO: Delete flavour $hash folder
//        $this->clean_temp_folder()
        
        // To avoid the html headers and all the print* stuff
        die();
    }
}
