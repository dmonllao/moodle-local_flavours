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
 * Form definition to settings to add to local/defaults.php
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to select the settings
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_generatedefaults_form extends moodleform implements renderable {

    public function definition() {

        global $USER, $CFG;

        $mform = & $this->_form;

        
        // Warning overwrite
        $mform->addElement('header', 'settings', get_string('defaultoverwritesoptions', 'local_flavours'));
        $mform->addElement('checkbox', 'overwrite', get_string('defaultsoverwrite', 'local_flavours'));
        
        // Settings
        $mform->addElement('header', 'ingredients',
            get_string('selectsettings', 'local_flavours'));
        $mform->addElement('html', '<div id="id_ingredients_tree" class="ygtv-checkbox">'.
            $this->_customdata["treedata"].'</div>');

        $mform->addElement('hidden', 'action', 'generatedefaults_execute');
        $mform->addElement('submit', 'ingredients_submit',
            get_string('generatedefaults', 'local_flavours'));
    }
}
