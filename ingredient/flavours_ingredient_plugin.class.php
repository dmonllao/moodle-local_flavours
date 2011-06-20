<?php 

require_once($CFG->dirroot . '/lib/pluginlib.php');
require_once($CFG->dirroot . '/local/flavours/ingredient/flavours_ingredient.class.php');

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
        
        // Moodle plugin manager and plugin types
        $pluginman = plugin_manager::instance();
        $plugintypes = get_plugin_types();
        
        foreach ($plugintypes as $type => $path) {
            
            $plugins = get_plugin_list($type);
            
            // We only add the plugin type if it has plugins
            if ($plugins) {
            
                // Core plugins
                if ($coreplugins = $pluginman->standard_plugins_list($type)) {
                    $coreplugins = array_combine($coreplugins, $coreplugins);
                }
        
                // The plugin type data
                $branchid = $type;
                $branchname = $pluginman->plugintype_name_plural($type);
                
                foreach ($plugins as $pluginname => $pluginpath) {
                    
                    // We will only list the non standard plugins
                    if (!empty($coreplugins) && !empty($coreplugins[$pluginname])) {
                        continue;
                    }
                    
                    $this->branches[$type]->branches[$pluginname]->id = $pluginname;
                    
                    // The plugin user friendly name
                    $pluginvisiblename = $this->get_system_plugin_visiblename($type, $pluginname);
                    $this->branches[$type]->branches[$pluginname]->name = $pluginvisiblename;
                }

                // Only if there is non core plugins
                if (empty($this->branches[$type]->branches)) {
                    continue;
                }
                
                $this->branches[$type]->id = $branchid;
                $this->branches[$type]->name = $branchname;
            }
        }
    }
    
    
    /**
     * Copies the selected plugins to the flavour file structure
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
        
        // Required to find plugin types paths
        $plugintypesdata = get_plugin_types();
        
        mkdir($path . '/' . $this->id, $CFG->directorypermissions);
        
        // A first iteration to group ingredients by plugin type
        foreach ($ingredientsdata as $plugintype) {
            $tmparray = explode('/', $plugintype);
            $plugins[$tmparray[0]][$tmparray[1]] = $tmparray[1];
        }
        
        foreach ($plugins as $plugintype => $ingredients) {
            
            $xmlwriter->begin_tag($plugintype);
            
            // The plugin type folder
            $plugintypepath = $plugintypesdata[$plugintype];
            $plugintypebasepath = str_replace($CFG->dirroot, '', $plugintypepath);
            $plugintypeflavourpath = str_replace(rtrim($CFG->dirroot, '/'), $path . '/' . $this->id, $plugintypepath);
            
            if (!mkdir($plugintypeflavourpath, $CFG->directorypermissions, true)) {
                debugging($plugintypeflavourpath);
                continue;
            }
            
            foreach ($ingredients as $ingredient) {
                    
                // Copying to the flavour filesystem
                $frompath = $plugintypepath . '/' . $ingredient;
                
                // Recursive copy
                $topath = $plugintypeflavourpath . '/' . $ingredient;
                if (!$this->copy($frompath, $topath)) {
                    debugging($frompath);
                    debugging($topath);
                    print_error('errorcopying', 'local_flavours');
                }
                
                // Adding the ingredient to the flavour data
                $xmlwriter->begin_tag($ingredient);
                $xmlwriter->full_tag('name', $this->get_system_plugin_visiblename($plugintype, $ingredient));
                $xmlwriter->full_tag('flavourpath', $this->id . '/' . $plugintype . '/' . $ingredient);
                $xmlwriter->full_tag('moodlepath', ltrim($plugintypebasepath, '/') . '/' . $ingredient);
                
                // The plugin version and required moodle version
                $pluginversion = $this->get_system_plugin_version($plugintypepath . '/' . $ingredient);
                $xmlwriter->full_tag('version', $pluginversion->version);
                $xmlwriter->full_tag('requires', $pluginversion->requires);
                $xmlwriter->end_tag($ingredient);
            }
            
            $xmlwriter->end_tag($plugintype);
        }

        return true;
    }
    
    
    /**
     * Returns (if possible) the visible name of the plugin
     * 
     * Not all the Moodle plugins follows the "pluginname" convention so let's 
     * display the plugin string identifier instead of the human readable pluginname
     * 
     * @param string $plugintype
     * @param string $pluginname
     * @return string The name to show
     */
    private function get_system_plugin_visiblename($plugintype, $pluginname) {
        
        $component = $plugintype.'_'.$pluginname;
        if (!get_string_manager()->string_exists('pluginname', $component)) {
            $pluginvisiblename = $pluginname;
        } else {
            $pluginvisiblename = get_string('pluginname', $component);
        }
        
        return $pluginvisiblename;
    }
    
    /**
     * Returns the version of the plugin from version.php
     * 
     * @param string $pluginpath
     * @return string The plugin version on moodle usual format
     */
    private function get_system_plugin_version($pluginpath) {
        global $CFG;
        
        $versionpath = $pluginpath . '/version.php';

        // If it doesn't exists we will add '' as the values
        if (file_exists($versionpath)) {
            include($versionpath);
        }
        
        if (!empty($plugin)) {
            $returnvalue = $plugin;
        } else if (!empty($module)) {
            $returnvalue = $module;
        }
        
        // No value then the deployment will know what to do
        if (empty($returnvalue) || empty($returnvalue->version)) {
            $returnvalue->version = '';
        }
        
        // No value then the deployment will know what to do
        if (empty($returnvalue) || empty($returnvalue->requires)) {
            $returnvalue->requires = '';
        }
        
        return $returnvalue;
    }
}
