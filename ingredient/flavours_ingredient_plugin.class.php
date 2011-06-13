<?php 

require_once(dirname(__FILE__) . '/flavours_ingredient.class.php');

/**
 * Manages the packaging and deployment of all the moodle plugins
 * 
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_ingredient_plugin extends flavours_ingredient {

    
    /**
     * Sets the ingredient name and identifier
     */
    public function __construct() {
        $this->id = 'plugin';
        $this->name = get_string('plugin');
    }
    

    /**
     * Gets the list of plugin types and the system available ingredients
     */
    public function get_system_data() {
        
        $plugintypes = get_plugin_types();
        foreach ($plugintypes as $type => $path) {
            
            $this->branches[$type]->id = $type;
            
            // TODO: Replace for a text string
            $this->branches[$type]->name = $type;
            
            $plugins = get_plugin_list($type);
            foreach ($plugins as $pluginname => $pluginpath) {
                
                $this->branches[$type]->branches[$pluginname]->id = $pluginname;
                
                // Not all the Moodle plugins follows the "pluginname" convention so let's 
                // display the plugin string identifier instead of the human readable pluginname
                $component = $type.'_'.$pluginname;
                if (!get_string_manager()->string_exists('pluginname', $component)) {
                    $pluginvisiblename = $pluginname;
                } else {
                    $pluginvisiblename = get_string('pluginname', $component);
                }
                
                $this->branches[$type]->branches[$pluginname]->name = $pluginvisiblename;
            }
        }
    }
    
    
    /**
     * 
     * @param xml_writer $xmlwriter The XML writer, by reference
     * @param string $path Where to store the data
     * @param array $ingredientsdata Ingredients to store
     */
    public function package_ingredients(&$xmlwriter, $path, $ingredientsdata) {
        
    }
    
}
