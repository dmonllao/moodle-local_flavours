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
        $PAGE->requires->js_init_call('M.local_flavours.render', null, true);

        // Fill the ingredients tree with this->ingredients (ondomready)
        $customdata['treedata'] = $this->get_tree_ingredients();

        $this->renderable = new flavours_generatedefaults_form($this->url, $customdata);
    }
    
    
    /**
     * Displays / writes the PHP code
     */
    public function generatedefaults_execute() {

        global $USER, $CFG;

        // Getting selected data
        $selectedingredients = $this->get_ingredients_from_form();
        if (!$selectedingredients) {
            $url = $CFG->wwwroot . '/local/flavours/index.php?action=generatedefaults_form' . 
                   '&sesskey=' . sesskey();
            redirect($url, get_string('nothingselected', 'local_flavours'), 2);
        }
        
        die(print_r($selectedingredients));
        if (is_writable($CFG->dirroot . '/local/defaults.php')) {
            
        } else {
            
        }
    }
}
