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
        
        $errorredirect = $this->url . '?action=deployment_upload&sesskey=' . sesskey();
        $previousform = new flavours_deployment_upload_form($this->url);
        
        // Redirect in no data was submitted
        if (!$formdata = $previousform->get_data()) {
            redirect($errorredirect, get_string('reselect', 'local_flavours'), 2);
        }
        
        // Creating the temp/ path
        $uniquename = md5($USER->id . '_' . time() . '_' . random_string(10));
        $flavourpath = $CFG->dataroot . '/temp/' . $uniquename;
        if (!mkdir($flavourpath, $CFG->directorypermissions)) {
            redirect($errorredirect, get_string('errordeployingpermissions', 'local_flavours'), 2);
        }
        
        // Saving the flavour file
        $flavourfilename = $flavourpath . '/flavour.zip';
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
        $xml = $this->get_flavour_xml($flavourzip);
        
        $flavourzip->close();
        
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
        }

        // Initializing the tree
        $PAGE->requires->js_init_call('M.local_flavours.init', null, true);
        
        // And rendering on dom ready
        $PAGE->requires->js_init_call('M.local_flavours.render', array('true'), true);

        // Fill the ingredients tree with this->ingredients (ondomready)
        $customdata['treedata'] = $this->get_tree_ingredients();
        $customdata['flavourhash'] = $uniquename;
        
        $this->form = new flavours_deployment_form($this->url, $customdata);
        $this->form->set_data($toform);
        
    }
    
    
    /**
     * Flavours deployment
     * 
     * Executes the deployment delegating to the specific ingredient types managers, it 
     * opens the flavour compressed file to extract the data and cleans the flavour temp
     * directory when finishes
     */
    public function deployment_execute() {
        global $CFG, $OUTPUT;
        
        $outputs = array();        // Deployment results
        
        $errorredirect = $this->url . '?action=deployment_upload&sesskey=' . sesskey();

        $form = new flavours_deployment_form($this->url);
        if (!$formdata = $form->get_data()) {
            $this->clean_temp_folder($flavourpath);
            redirect($errorredirect, get_string('reselect', 'local_flavours'), 2);
        }

        // Flavour contents
        $flavourpath = $CFG->dataroot . '/temp/' . $formdata->flavourhash;
        $flavourfilename = $flavourpath . '/flavour.zip';
        
        // Getting the ingredients to deploy
        if (!$flavouringredients = $this->get_ingredients_from_form()) {
            $this->clean_temp_folder($flavourpath);
            redirect($errorredirect, get_string('reselect', 'local_flavours'), 2);
        }
        
        // Getting zip contents
        if (!unzip_file($flavourfilename, $flavourpath, false)) {
            print_error('errorcantunzip', 'local_flavours');
        }
        
        $flavourzip = new ZipArchive();
        if (!$flavourzip->open($flavourfilename, 0)) {
            $this->clean_temp_folder($flavourpath);
            redirect($errorredirect, get_string('errordeployflavour', 'local_flavours'), 4);
        }
        
        // Getting the flavour xml which describes the flavour contents        
        $xml = $this->get_flavour_xml($flavourzip);
        
        // Deploying ingredients when possible
        foreach ($flavouringredients as $type => $ingredientstodeploy) {
            
            $this->ingredients[$type] = $this->instance_ingredient_type($type);
            
            // Ingredient type filesystem
            $ingredienttypepath = $flavourpath . '/flavour/' . $type;
            if (!file_exists($ingredienttypepath)) {
                $ingredienttypepath = false;
            }
            
            // Deploying ingredients and storing the problems encountered to give feedback
            $xmldata = $xml->ingredient[0]->$type; 
            $outputs[$type] = $this->ingredients[$type]->deploy_ingredients($ingredientstodeploy, 
                $ingredienttypepath, $xmldata);
                
            // Prepare to display deployment results
            foreach ($ingredientstodeploy as $ingredientname => $ingredientdata) {
            	if (empty($outputs[$type][$ingredientname])) {
            		$outputs[$type][$ingredientname] = true;
            	}
            }
        }
        
        // Output results
        $table = new html_table();
        $table->attributes['class'] = 'generaltable boxaligncenter';
        $table->align = array('left', 'left', 'center');
        $table->head  = array(get_string('ingredienttype', 'local_flavours'),
            get_string('ingredient', 'local_flavours'),
            get_string('deploymentresult', 'local_flavours'));

        // Fill the table
        foreach ($outputs as $type => $ingredients) {
        	foreach ($ingredients as $ingredientname => $outputs) {
        		
        		// Success
        		if (is_bool($outputs)) {
        			$feedback = get_string('success');
        			$classname = 'flavours_deployment_success';
        		} else {
        			$feedback = $this->get_restrictions_string($outputs);
        			$classname = 'error';
        		}
        		
        		$feedback = '<span class="' . $classname . '">' . $feedback . '</span>';
        		$table->data[] = array($this->ingredients[$type]->name, $ingredientname, $feedback);
        	}
        }
        $this->output = html_writer::table($table);
        
        // Finishing
        $this->clean_temp_folder($flavourpath);
        
        // TODO: Add a button/link to 'Notifications' to install/upgrade plugins
        // TODO: Add a button/link to 'Update the deployed language packs
    }
    
    
    /**
     * Returns the flavour XML which describes the flavour contents
     * @param ZipArchive $flavourzip
     * @return SimpleXMLElement
     */
    protected function get_flavour_xml(ZipArchive $flavourzip) {

        $flavourxml = $flavourzip->getFromName('flavour/flavour.xml');
        
        // Parsing the .xml content to extract flavour info
        return simplexml_load_string($flavourxml);
    }
    
}
