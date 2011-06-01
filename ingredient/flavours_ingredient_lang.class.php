<?php 

require_once(dirname(__FILE__) . '/flavours_ingredient.class.php');


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
        
        $this->branch->id = 'lang';
        
        // TODO: Find the correct string
//        $this->name = get_string('lang');
        $this->branch->name = 'Languages';
    }
    

    /**
     * Gets the installed languages
     */
    public function get_system_data() {
        
        $langs = get_string_manager()->get_list_of_translations();
        foreach ($langs as $lang => $langname) {
            $this->branch->branches[$lang]->id = $lang;
            $this->branch->branches[$lang]->name = $langname;
        }
    }
    
}
