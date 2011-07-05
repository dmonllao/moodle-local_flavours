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
     * Sets the ingredient name and identifier
     */
    public function __construct() {
        $this->id = 'lang';
        $this->name = get_string('language');
    }
    

    /**
     * Gets the installed languages
     */
    public function get_system_data() {
        
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

            // All the languages are stored in dataroot, english is the only exception AFAIK
            $frompath = $CFG->dataroot . '/lang/' . $langid;
            if (!file_exists($frompath)) {
                $frompath = $CFG->dirroot . '/lang/' . $langid;
            }
                
            // Recursive copy
            $topath = $path . '/lang/' . $langid;
            if (!$this->copy($frompath, $topath)) {
                debugging($frompath);
                debugging($topath);
                print_error('errorcopying', 'local_flavours');
            }
            $language = get_string_manager()->load_component_strings('langconfig', $langid);
            $xmlwriter->begin_tag($langid);
            $xmlwriter->full_tag('name', $language['thislanguage']);
            $xmlwriter->full_tag('path', 'lang/' . $langid);
                
            // Moodle doesn't have a language versioning system
            // $xmlwriter->full_tag('version', ...);
            $xmlwriter->end_tag($langid);
        }
        
        return true;
    }
    
    
    /**
     * Gets the languages availables on the flavour
     * @param SimpleXMLElement $xml
     */
    public function get_flavour_data($xml) {
        
        $langs = get_string_manager()->get_list_of_translations();
        
        foreach ($xml as $lang => $langdata) {
            
            // Is a valid lang?
            if (!empty($langs[$lang])) {
	            $this->branches[$lang]->id = $lang;
	            $this->branches[$lang]->name = $langdata->name;
            }
        }
    }
    
}
