<?php 

require_once(dirname(__FILE__) . '/flavours_ingredient.class.php');


/**
 * Manages the packaging and deployment of administration settings
 * 
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_ingredient_setting extends flavours_ingredient {
    
    /**
     * Sets the ingredient name and identifier
     */
    public function __construct() {
        $this->id = 'setting';
        $this->name = get_string('settings');
    }
    
    
    /**
     * Gets the admin tree instance and populates $this->branch  
     * with the admin tree categories and pages
     */
    public function get_system_data() { 
        $adminroot = & admin_get_root();
        $this->get_branch_settings($adminroot->children, $this);
    }
    

    /**
     * Writes the xml with the selected admin settings
     * 
     * @param xml_writer $xmlwriter The XML writer, by reference
     * @param string $path Where to store the data
     * @param array $ingredientsdata Settings to store
     */
    public function package_ingredients(&$xmlwriter, $path, $ingredientsdata) {
        
        if (!$ingredientsdata) {
            return false;
        }
        
        $adminroot = & admin_get_root();
        $this->get_branch_settings($adminroot->children, $this, true);

        // TODO: Monitor performance
        foreach ($ingredientsdata as $settingspage) {
            
	        // Settings page path
	        $namespace = explode('/', $settingspage);
	            
	        // The admin settingspage is the last one
	        $page = array_pop($namespace);
	            
            if (!$settings = $this->get_settingspage_settings($namespace, $page, $this)) {
                continue;
            }
            
            $settingspagetagname = str_replace('/', '.', $settingspage);
            $xmlwriter->begin_tag($settingspagetagname);
            
            // Adding settings
            foreach ($settings as $setting) {
                $xmlwriter->full_tag($setting->name, $this->get_setting_value($setting->name, $setting->plugin));
            }
            
            $xmlwriter->end_tag($settingspagetagname);
        }

        return true;
    }
        
    
    /**
     * Iterates through the moodle admin tree to extract the settings categories & pages hierarchy
     * 
     * @param object $admintreebranch
     * @param object $branch
     * @param boolean $addsettings Should settings be included?
     */
    protected function get_branch_settings($admintreebranch, &$branch, $addsettings = false) {

        foreach ($admintreebranch as $key => $child) {

            // Adding settings category and it's children
            if (is_a($child, 'admin_category')) {

                if ($child->children) {
                    $branch->branches[$child->name]->id = $child->name;
                    $branch->branches[$child->name]->name = $child->visiblename;

                    // Adding branch branches
                    $this->get_branch_settings($child->children, $branch->branches[$child->name], $addsettings);
                }

            // Adding the settings pages if we find settings
            } else if (is_a($child, 'admin_settingpage') && $child->settings) {
                $branch->branches[$child->name]->id = $child->name;
                $branch->branches[$child->name]->name = $child->visiblename;
                
                // Adding the settings if required
                if ($addsettings && !empty($child->settings)) {
                    foreach ($child->settings as $settingname => $setting) {
                        
                        // TODO: Take into account the _with_advanced....
                        if ($setting->plugin == '') {
                            $branch->branches[$child->name]->settings[$settingname]->plugin = 'core';
                        } else {
                            $branch->branches[$child->name]->settings[$settingname]->plugin = $setting->plugin;
                        }
                        $branch->branches[$child->name]->settings[$settingname]->name = $settingname;
                    }
                }
            }
        }
    }
    
    
    /**
     * Returns all the settings of a settingspage
     * 
     * @param array $settingspagenamespace The path of the settingspage inside the admin tree
     * @param string $settingspage The name of the settings page
     * @param admin_tree $settingpagebranch Where to search
     * @return array An array of admin_settings
     */
    protected function get_settingspage_settings($settingspagenamespace, $settingspage, $settingpagebranch) {

        // Iteration through the namespace to locate the settingspage and get it's settings
        foreach ($settingspagenamespace as $settingscategory) {
            if (empty($settingpagebranch->branches[$settingscategory])) {
                print_error('errorunknownsettingspage', 'local_flavours', $settingspage);
            }
            $settingpagebranch = $settingpagebranch->branches[$settingscategory];
        }
            
        // It there aren't available settings skip it
        if (empty($settingpagebranch->branches[$settingspage]->settings)) {
            return false;
        }
        
        return $settingpagebranch->branches[$settingspage]->settings;
    }
    
    
    /**
     * Gets the setting value
     * 
     * @todo Take into account site name and things like that which are not stored on config* tables
     * @param string $name
     * @param string $plugin
     * @return mixed
     */
    protected function get_setting_value($name, $plugin) {
        
        global $CFG;
        
        return get_config($plugin, $name);
    }
       
}
