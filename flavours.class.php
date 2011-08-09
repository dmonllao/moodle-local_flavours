<?php 

/**
 * Common methods
 */
abstract class flavours {

    protected $action;
    protected $url;
    protected $flavourstmpfolder;
    protected $ingredienttypes;
    
    protected $output;
    protected $form;
    
    public function __construct($action) {
        
        global $CFG;
        
        $this->action = $action;
        $this->url = $CFG->wwwroot.'/local/flavours/index.php';
        $this->flavourstmpfolder = $CFG->dataroot.'/temp/flavours';
        
        // Ensure that the flavours temp folder exists
        if (!file_exists($this->flavourstmpfolder)) {
            mkdir($this->flavourstmpfolder, $CFG->directorypermissions);
        }
        
        // TODO: Think of a way to clean the temp folders on workflow exceptions
        
        // Getting the system ingredient types
        $this->set_ingredient_types();
    }
    
    
    /**
     * Creates the tree structure based on $this->ingredients
     * 
     * @return array The tree html following the TreeView structure
     */
    protected function get_tree_ingredients() {
        
        global $PAGE;

        if (empty($this->ingredients)) {
            return false;
        }

        $treedata = '<ul>';
        foreach ($this->ingredients as $ingredienttype => $branch) {

            // A main ingredient/ namespace to ease the ingredients detection
            $prefix = 'ingredient/'.$ingredienttype;
            $this->get_tree_data($treedata, $branch, $prefix);
        }
        $treedata .= '</ul>';
        
        return $treedata;
    }
    
    
    /**
     * Gets the html to display the tree
     * 
     * @param string $output
     * @param object $branch
     * @param string $prefix
     */
    protected function get_tree_data(&$output, $branch, $prefix) {
        
        $output .= '<li>';
        $output .= $branch->name;
        
        $output .= '<ul>';
        if (!empty($branch->branches)) {

	        foreach ($branch->branches as $name => $data) {

	            // To identify that branch/leaf and pass it through his branches
	            $branchprefix = $prefix.'/'.$data->id;

	            // If it does not have children it's a leaf
	            if (empty($data->branches)) {
	                
	                $string = $data->name;
	                
	                // Should we add restrictions info??
	                if (!empty($data->restrictions)) {
	                    $string .= ' <span class="error">';
	                    $string .= $this->get_restrictions_string($data->restrictions);
	                    $string .= '</span>';
	                }
	                $output .= '<li><a target="'.$branchprefix.'">'.$string.'</strong></a></li>';
	
	            // Let's get the branch children
	            } else {
	                $this->get_tree_data($output, $data, $branchprefix);
	            } 
	        }
        }
        
        $output .= '</ul>';
        $output .= '</li>';
    }
    
    
    /**
     * Returns a new instance of a ingredient_type
     * 
     * @param string $type The ingredient type
     * @param flavours_ingredient $type
     */
    protected function instance_ingredient_type($type) {
        
        global $CFG;
        
        $classname = 'flavours_ingredient_'.$type;
        $filepath = $CFG->dirroot . '/local/flavours/ingredient/'.$classname.'.class.php';
        if (!file_exists($filepath)) {
            print_error('ingredienttypenotavailable', 'local_flavours');
        }

        // Getting the system ingredients of that type 
        require_once($filepath);
        
        return new $classname();
    }
    /**
     * Sets the page info and returns the header to output
     */
    public function print_header() {
        
        global $PAGE, $OUTPUT, $SITE;
        
        $actualsettingspage = array_shift(explode('_', $this->action));
        admin_externalpage_setup('local_flavours_'.$actualsettingspage);
            
        $PAGE->set_heading($SITE->fullname);
        $PAGE->set_title(get_string('action'.$this->action, 'local_flavours'));
            
        return $OUTPUT->header();
    }

    
    /**
     * Centralized output
     */
    public function display() {
        
        global $OUTPUT;
        
        echo $this->print_header();
        
        if (!empty($this->form)) {
            $this->form->display();
        }
        echo $this->output;
        echo $OUTPUT->footer();
    }
    
    
    /**
     * Extracts the selected ingredients from $_POST
     * @return array The selected ingredients organized by ingredient type
     */
    public function get_ingredients_from_form() {
        
        if (empty($_POST)) {
            return false;
        }
        
        // Looking for selected ingredients and the ingredient type
        foreach ($_POST as $varname => $enabled) {
            
            if (strstr($varname, '/') == false) {
                continue;
            }
            
            $namespace = explode('/', $varname);
            if (array_shift($namespace) == 'ingredient') {
                
                $ingredienttype = array_shift($namespace);
                
                $namespace = preg_replace('/[^a-zA-Z_]/i', '', $namespace);
                $ingredientpath = implode('/', $namespace);

                // Only organized by ingredient type, each type will  
                // treat his ingredients on a different way
                $ingredients[$ingredienttype][$ingredientpath] = $ingredientpath;
            }
        }
        
        if (empty($ingredients)) {
            return false;
        }
        
        return $ingredients;
    }
    

    /**
     * Sets the available ingredient types of the system
     * 
     * Reads the flavours/ingredient/ folder to extract the ingredient names
     * from the available classes, excluding the parent class
     */
    protected function set_ingredient_types() {
    	global $CFG;

    	$this->ingredienttypes = array();
    	 
    	$ingredientsdir = $CFG->dirroot . '/local/flavours/ingredient/';
    	if ($dirhandler = opendir($ingredientsdir)) {
    		while (($file = readdir($dirhandler)) !== false) {
    			
    			// Excluding the parent class (and maybe SCV hidden files or something like that)
    			preg_match('/flavours_ingredient_(.*?).class.php/', $file, $ingredienttype);
    			if ($ingredienttype) {
    				$this->ingredienttypes[] = $ingredienttype[1];
    			}
    		}
    		closedir($dirhandler);
    	}
    }
    
    
    /**
     * Recursive implementation of unlink() to remove directories
     *
     * @param string $path The path to remove
     * @return boolean
     */
    public function unlink($path) {
    
        if ($path == false || $path == '') {
            return false;
        }
        
        $status = true;
        
        if (!is_dir($path)) {
        	$status = @unlink($path);

        } else {
        	$dir = opendir($path);
            while (false !== ($file = readdir($dir))) {
                if ($file == "." || $file == "..") {
                    continue;
                }

                $status = $status && $this->unlink($path . '/' . $file);
            }
            closedir($dir);

            $status = $status && @rmdir($path);
        }
        
        return $status;
    }
    

    /**
     * The restrictions in a string
     * 
     * @param array $restrictions
     * @return string
     */
    protected function get_restrictions_string($restrictions) {
        
        $strs = array();
        if ($restrictions) {
            foreach ($restrictions as $restriction => $a) {
                $strs[] = get_string('restriction'.$restriction, 'local_flavours', $a);
            }
        }

        
        return implode(' / ', $strs);
    }
}
