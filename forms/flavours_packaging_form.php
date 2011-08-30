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
 * Form definition to selec system ingredients to package
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to select the flavour ingredients
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_packaging_form extends moodleform implements renderable {

    public function definition() {

        global $USER;

        $mform = & $this->_form;

        // General data
        $mform->addElement('header', 'flavourdata', get_string('flavourdata', 'local_flavours'));

        $mform->addElement('text', 'name', get_string('flavourname', 'local_flavours'));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('htmleditor', 'description',
            get_string('flavourdescription', 'local_flavours'));
        $mform->setType('description', PARAM_CLEANHTML);

        $mform->addElement('text', 'author', get_string('flavourauthor', 'local_flavours'),
            'maxlength="154" size="40"');
        $mform->setType('author', PARAM_TEXT);
        $mform->setDefault('author', $USER->firstname.' '.$USER->lastname);

        // Ingredients
        $mform->addElement('header', 'ingredients',
            get_string('selectingredients', 'local_flavours'));
        $mform->addElement('html', '<div id="id_ingredients_tree" class="ygtv-checkbox">'.
            $this->_customdata["treedata"].'</div>');

        $mform->addElement('hidden', 'action', 'packaging_execute');
        $mform->addElement('submit', 'ingredients_submit',
            get_string('packageflavour', 'local_flavours'));
    }
}
