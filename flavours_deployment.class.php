<?php 

require_once($CFG->libdir.'/filestorage/zip_packer.php');

require_once($CFG->dirroot . '/local/flavours/flavours.class.php');
require_once($CFG->dirroot . '/local/flavours/forms/flavours_deployment_upload_form.php');
require_once($CFG->dirroot . '/local/flavours/forms/flavours_deployment_form.php');

/**
 * Manages the deployment steps
 * 
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_deployment extends flavours {
    

    /**
     * Initialization of the upload form
     */
    public function deployment_upload() {
        $this->form = new flavours_deployment_upload_form($this->url);
    }
    
    
    /**
     * Flavour contents preview
     * 
     * Based on the flavour.xml data, if there are flavour file structure problems like
     * missing files/directories it will be checked on the deployment execution
     */
    public function deployment_preview() {
        global $USER, $CFG, $PAGE;
        
        $errorredirect = $this->url.'?action=deployment_upload';
        $previousform = new flavours_deployment_upload_form($this->url);
        
        // Redirect in no data was submitted
        if (!$formdata = $previousform->get_data()) {
            redirect($errorredirect, get_string('reselect', 'local_flavours'), 2);
        }
        
        // Creating the temp/ path
        $uniquename = md5($USER->id.'_'.time().'_'.random_string(10));
        $flavourpath = $CFG->dataroot.'/temp/'.$uniquename;
        if (!mkdir($flavourpath, $CFG->directorypermissions)) {
            redirect($errorredirect, get_string('errordeployingpermissions', 'local_flavours'), 2);
        }
        
        // Saving the flavour file
        $flavourfilename = $flavourpath.'/flavour.zip';
        if (!$previousform->save_file('flavourfile', $flavourfilename, true)) {
            $this->clean_temp_folder($flavourpath);
            redirect($errorredirect, get_string('errordeployflavour', 'local_flavours'), 4);
        }
        
        // Opening zip
        $flavourzip = new ZipArchive();
        if (!$flavourzip->open($flavourfilename, 0)) {
            $this->clean_temp_folder($flavourpath);
            redirect($errorredirect, get_string('errordeployflavour', 'local_flavours'), 4);
        }
        
        // Getting the flavour xml which describes the flavour contents
        $flavourxml = $this->get_flavour_xml($flavourzip);
        $flavourzip->close();
        
        // Parsing the .xml content to extract flavour info
        $xml = simplexml_load_string($flavourxml);

        $toform = new stdClass();
        $toform->name = $xml->name[0];
        $toform->description = $xml->description;
        $toform->author = $xml->author;
        $toform->timecreated = $xml->timecreated;
        $toform->moodlerelease = $xml->moodlerelease;
        $toform->moodleversion = $xml->moodleversion;
        
        // Adding the over-write value from the upload flavour form and adding it as hidden
        $toform->overwrite = $formdata->overwrite;

        // Fill $this->ingredients with the xml ingredients
        foreach ($xml->ingredient[0] as $type => $ingredientsxml) {
            
            $this->ingredients[$type] = $this->instance_ingredient_type($type);
            
            // It also looks for restrictions like file permissions, plugins already added
            $this->ingredients[$type]->get_flavour_info($ingredientsxml);
            
            // Gathering restrictions
            $customdata['restrictions'][$type] = $this->ingredients[$type]->restrictions;
        }

        // Initializing the tree
        $PAGE->requires->js_init_call('M.local_flavours.init', null, true);
        
        // And rendering on dom ready
        $PAGE->requires->js_init_call('M.local_flavours.render', null, true);

        // Fill the ingredients tree with this->ingredients (ondomready)
        $customdata['treedata'] = $this->get_tree_ingredients();
        
        $this->form = new flavours_deployment_form($this->url, $customdata);
        $this->form->set_data($toform);
        
        // TODO: Delete the temp/ folder
    }
    
    
    /**
     * Flavours deployment results
     */
    public function deployment_execute() {
        
        $errorredirect = $this->url.'?action=deployment_upload';
        
        $form = new flavours_deployment_form($this->url);
        if (!$formdata = $form->get_data()) {
            redirect($errorredirect, get_string('reselect', 'local_flavours'), 2);
        }
        
        notify(print_r($formdata));
        notify(print_r($_POST));
    }
    
    
    /**
     * Gets the flavour main .xml with the flavour contents description
     * @param ZipArchive $flavourzip
     * @return string
     */
    protected function get_flavour_xml(ZipArchive $flavourzip) {
        return $flavourzip->getFromName('flavour/flavour.xml');
    }
}
