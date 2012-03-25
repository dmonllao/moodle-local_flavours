<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Flavours local/defaults generator
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/flavours/flavours.class.php');
require_once($CFG->dirroot . '/local/flavours/forms/flavours_generatedefaults_form.php');

class flavours_generatedefaults extends flavours {

    
    /**
     * Allows users to select which settings will be saved
     */
    public function generatedefaults_form() {

        global $CFG, $PAGE;

        $this->ingredients['setting'] = $this->instance_ingredient_type('setting');
        $this->ingredients['setting']->get_system_info();
        
        // Initializing the tree
        $PAGE->requires->js_init_call('M.local_flavours.init', null, true);

        // And rendering on dom ready
        $PAGE->requires->js_init_call('M.local_flavours.render', array(true), true);

        // Fill the ingredients tree with this->ingredients (ondomready)
        $customdata['treedata'] = $this->get_tree_ingredients();

        $this->renderable = new flavours_generatedefaults_form($this->url, $customdata);
    }
    
    
    /**
     * Displays / writes the PHP code
     * @param boolean $overwrite Forces local/defaults.php to be overwritten
     * @param array $selectedingredients Forces the selected ingredients
     */
    public function generatedefaults_execute($overwrite = false, $selectedingredients = false) {

        global $USER, $CFG;

        // For cli execution
        if (!$overwrite) { 
            $overwrite = optional_param('overwrite', false, PARAM_INT);
        }

        // Getting selected data
        // Second argument true when generating the settings from cli
        if (!$selectedingredients) {
            $selectedingredients = $this->get_ingredients_from_form();
        }
        if (!$selectedingredients) {
            $url = $CFG->wwwroot . '/local/flavours/index.php?action=generatedefaults_form' . 
                   '&sesskey=' . sesskey();
            redirect($url, get_string('nothingselected', 'local_flavours'), 2);
        }
        
        // Delegating to flavours_ingredient_setting
        $settingingredient = $this->instance_ingredient_type('setting');
        $phparray = $settingingredient->settings_to_php($selectedingredients['setting']);
        
        // Depending on the form checkbox
        if ($overwrite) {
            
	        // Try to write file
	        $path = $CFG->dirroot . '/local';
	        $file = $path .'/defaults.php';
	        
	        if (is_writable($path) && !file_exists($file) || 
	            is_writable($file)) {
	            
	            $fh = fopen($file, 'w');
	            fwrite($fh, '<?php' . chr(10) . chr(10));
	            fwrite($fh, implode(chr(10), $phparray));
	            fclose($fh);

	            $info->text = get_string('defaultsfileoverwritten', 'local_flavours');
	            $info->class = 'notifysuccess';
	        } else {
	            $info->class = 'notifyproblem';
	            $info->text = get_string('errordefaultfilenotwritable', 'local_flavours');
	        }
	        
	    // Add an info text to copy & paste
        } else {
            $info->text = get_string('copyandpastedefaults', 'local_flavours');
            $info->class = '';
        }

        // Sends the PHP code and the info about local/defaults.php to the rendere
        $renderer = new flavours_renderable_generatedefaults_execute($phparray, $info);
        $this->renderable = $renderer;
    }
}
