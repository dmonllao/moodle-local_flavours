<?php 

require_once($CFG->dirroot . '/local/flavours/ingredient/flavours_ingredient.class.php');


/**
 * Manages the packaging and deployment of language packs
 * 
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_ingredient_lang extends flavours_ingredient {

	
    /**
     * The path where the language packs are stored
     * @var string
     */
	protected $langpath;
	
    /**
     * Sets the ingredient name and identifier
     */
    public function __construct() {
    	global $CFG;
    	
        $this->id = 'lang';
        $this->name = get_string('language');
        
        $this->langpath = $CFG->dataroot . '/lang/';
    }
    

    /**
     * Gets the installed languages
     */
    public function get_system_info() {
        
        $langs = get_string_manager()->get_list_of_translations();
        if ($langs) {
            foreach ($langs as $lang => $langname) {
                if ($lang != 'en') {
                    $this->branches[$lang]->id = $lang;
                    $this->branches[$lang]->name = $langname;
                }
            }
        }
    }

    
    /**
     * Copies the selected languages to the temp path
     * 
     * @param xml_writer $xmlwriter The XML writer, by reference
     * @param string $path Where to store the data
     * @param array $ingredientsdata Ingredients to store
     */
    public function package_ingredients(&$xmlwriter, $path, $ingredientsdata) {
        
        global $CFG;
        
        if (!$ingredientsdata) {
            return false;
        }
            
        mkdir($path . '/' . $this->id, $CFG->directorypermissions);

        foreach ($ingredientsdata as $langid) {

        	// External method to allow methods overwrite
        	$langdirname = $this->get_lang_dir($langid);
        	
            // All the languages are stored in dataroot, english is the only exception AFAIK
            $frompath = $this->langpath . '/' . $langdirname;
                
            // Recursive copy
            $topath = $path . '/' . $this->id . '/' . $langdirname;
            if (!$this->copy($frompath, $topath)) {
                debugging($frompath);
                debugging($topath);
                print_error('errorcopying', 'local_flavours');
            }
            $language = get_string_manager()->load_component_strings('langconfig', $langid);
            $xmlwriter->begin_tag($langid);
            $xmlwriter->full_tag('name', $language['thislanguage']);
            $xmlwriter->full_tag('path', $this->id . '/' . $langid);
                
            // Moodle doesn't have a language versioning system
            // $xmlwriter->full_tag('version', ...);
            $xmlwriter->end_tag($langid);
        }
        
        return true;
    }
    
    
    /**
     * Gets the languages availables on the flavour
     * 
     * It also loads $this->restrictions with ->general and ->specific attributes (array type both). 
     * The array key will be used to get the language string
     * 
     * @param SimpleXMLElement $xml
     */
    public function get_flavour_info($xml) {
        global $CFG;
        
        $systemlangs = get_string_manager()->get_list_of_translations();
        $alllangs = get_string_manager()->get_list_of_languages();
        
        // File permissions
        $langsfolder = $CFG->dataroot.'/lang/';
        if (!is_writable($langsfolder)) {
            $nowritable = true;
        }
        
        $ingredients = $xml->children();
        foreach ($ingredients as $lang => $langdata) {

            // Writable directory?
            if (!empty($nowritable)) {
                $this->branches[$lang]->restrictions['langfilepermissions'] = $langsfolder;
            }
            
            // Installed language?
            if (!empty($systemlangs[$lang])) {
                $this->branches[$lang]->restrictions['langalreadyinstalled'] = $lang;
            }
              
            // Valid language?
            if (empty($alllangs[$lang])) {
                $this->branches[$lang]->restrictions['langnotvalid'] = $lang;
                
            }
            
            $this->branches[$lang]->id = $lang;
            $this->branches[$lang]->name = $langdata->name;
        }

    }
    
    
    /**
     * Installs the selected languages
     * 
     * @todo Language md5 verification?
     * @param array $ingredients
     * @param string $path Path to the ingredient type file system
     * @param SimpleXMLElement $xml
     * @return array Problems during the ingredients deployment
     */
    public function deploy_ingredients($ingredients, $path, SimpleXMLElement $xml) {
        global $CFG;
        
        // Checking again and storing data in $this->branches
        $this->get_flavour_info($xml);

        $problems = array();
        foreach ($ingredients as $ingredient) {

            // Only if there are no problems with the ingredient
            if (!empty($this->branches[$ingredient]->restrictions)) {
                $problems[$ingredient] = $this->branches[$ingredient]->restrictions;
                continue;
            }
            
            $langpath = $CFG->dataroot . '/lang/' . $this->get_lang_dir($ingredient);
            mkdir($langpath, $CFG->directorypermissions);
            
            $tmplangpath = $path . '/' . $this->get_lang_dir($ingredient);
            if (!$this->copy($tmplangpath, $langpath)) {
            	debugging('From: ' . $tmplangpath . ' To: '.$langpath);
            	$problems[$ingredient]['langfilepermissions'] = true;
            }
        }
        
        return $problems;
    }
    
    
    /**
     * Returns the dir name of the language
     * 
     * Useful to allow overwrite of methods
     * 
     * @param string $langid
     * @return string
     */
    protected function get_lang_dir($langid) {
    	return $langid;
    }
}
