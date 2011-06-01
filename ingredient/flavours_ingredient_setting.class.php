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
        $this->branch->id = 'setting';
        $this->branch->name = get_string('settings');
    }
    
    
    /**
     * Gets the admin tree instance and populates $this->branch  
     * with the admin tree categories and pages
     */
    public function get_system_data() { 
        $adminroot = & admin_get_root(false, true);
        $this->get_branch_settings($adminroot->children, $this->branch);
    }
    

    /**
     * Iterates through the moodle admin tree to extract the settings categories & pages hierarchy
     * 
     * @param object $admintreebranch
     * @param object $branch
     */
    protected function get_branch_settings($admintreebranch, &$branch) {

        foreach ($admintreebranch as $key => $child) {

            // Adding settings category and it's children
            if (is_a($child, 'admin_category')) {

                if ($child->children) {
                    $branch->branches[$child->name]->id = $child->name;
                    $branch->branches[$child->name]->name = $child->name;

                    // Adding branch branches
                    $this->get_branch_settings($child->children, $branch->branches[$child->name]);
                }

            // Adding the settings pages if we find settings
            } else if (is_a($child, 'admin_settingpage') && $child->settings) {
                $branch->branches[$child->name]->id = $child->name;
                $branch->branches[$child->name]->name = $child->visiblename;
            }
        }
    }
    
}
