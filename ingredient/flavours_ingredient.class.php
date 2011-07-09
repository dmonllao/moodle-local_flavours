<?php 

/**
 * Abstract class to define the ingredients interface
 * 
 * @abstract
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class flavours_ingredient {
    
    
    /**
     * Ingredient type id
     * @var string
     */
    public $id;
    
    
    /**
     * Name given to the ingredient type, to identify it on the ingredients tree
     * @var string
     */
    public $name;
    
    
    /**
     * The ingredient type ingredients
     * @var array
     */
    public $branches;
    
    
    /**
     * Contains the restriction found when deploying
     * 
     * Two attributes, general for ingredient-type common issues and 
     * specific to specific ingredients
     * 
     * @var array
     */
    public $restrictions;
    
    
    /**
     * Gets an ingredients list with the ingredients availables on the system
     * @abstract
     */
    abstract public function get_system_info();

    
    /**
     * Stores the selected ingredients into the flavour folder
     * 
     * @abstract
     * @param xml_writer $xmlwriter The XML writer, by reference
     * @param array $ingredients The ingredients to store
     * @param string $path Where to store the flavour tmp files
     * @return boolean Not treated but true if it adds something
     */
    abstract public function package_ingredients(&$xmlwriter, $ingredients, $path);
    
    
    /**
     * Gets an ingredients list with the ingredients availables on a flavour
     * @abstract
     * @param SimpleXMLElement $xml
     */
    abstract public function get_flavour_info($xml);
    
    
    /**
     * Checks if the target system accomplishes the flavour ingredients requirements
     * 
     * Disabled by default, but all the ingredient types should overwrite it
     * 
     * 
     * 
     */
    public function check_target_system(){}
    
    
//    abstract public function deploy_ingredients();
    
    
    /**
     * Support function - copied from backup/lib.php and adapted to avoid SCV files
     * 
     * @param string $from
     * @param string $to
     * @return boolean Feedback
     */
    protected function copy($from, $to) {
        
        global $CFG;

        if (!file_exists($from)) {
            return false;
        }
        
        // SCV systems to avoid
        $scvs = array('.git', 'CVS', '.svn');
        $scvsdirs = array_combine($scvs, $scvs);
        
        $status = true; // Initialize this, next code will change its value if needed
        
        if (is_file($from)) {
            umask(0000);
            if (!copy($from, $to)) {
                $status = false;
            } else {
                chmod($to, $CFG->directorypermissions);
                $status = true;
            }
            
        } else {
            
            if (!is_dir($to)) {
                umask(0000);
                $status = mkdir($to, $CFG->directorypermissions);
            }
            
            $dir = opendir($from);
            while (false !== ($file=readdir($dir))) {
                
                // We don't want SCVS files
                if ($file=="." || $file==".." || !empty($scvsdirs[$file])) {
                    continue;
                }
                $status = $this->copy("$from/$file", "$to/$file");
            }
            closedir($dir);
        }
        
        return $status;
    }

}
