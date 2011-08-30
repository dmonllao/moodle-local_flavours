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
 * To select which ingredients will be deployed
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to select the ingredients to deploy
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_deployment_form extends moodleform implements renderable {

    public function definition() {

        $mform = & $this->_form;

        // Step header
        $steplabel = get_string('deploymentpreviewheader', 'local_flavours');
        $mform->addElement('header', 'flavourdata', $steplabel);

        // Flavour info
        $fields = array('name', 'description', 'author', 'timecreated', 'sourceurl',
            'sourcemoodlerelease', 'sourcemoodleversion');
        foreach ($fields as $field) {
            $label = '<strong>'.get_string('flavour' . $field, 'local_flavours').'</strong>';
            $mform->addElement('static', $field, $label);
        }

        // Ingredients
        $mform->addElement('header', 'ingredients',
            get_string('selectingredients', 'local_flavours'));
        $mform->addElement('html', '<div id="id_ingredients_tree" class="ygtv-checkbox">'.
            $this->_customdata["treedata"].'</div>');

        $mform->addElement('hidden', 'overwrite');
        $mform->addElement('hidden', 'flavourhash', $this->_customdata['flavourhash']);
        $mform->addElement('hidden', 'action', 'deployment_execute');
        $mform->addElement('submit', 'ingredients_submit',
            get_string('deployflavour', 'local_flavours'));
    }
}
