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
 * To upload a flavour
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Form to upload a flavour
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_deployment_upload_form extends moodleform implements renderable {

    public function definition () {

        global $CFG;

        $mform = & $this->_form;

        $steplabel = get_string('deploymentuploadheader', 'local_flavours');
        $mform->addElement('header', 'general', $steplabel);

        // File picker
        $mform->addElement('filepicker', 'flavourfile', get_string('selectfile', 'local_flavours'));
        $mform->addRule('flavourfile', null, 'required');

        // Overwrite
        $overwritelabel = get_string('overwrite', 'local_flavours');
        $overwriteoptions = array(0 => get_string('overwriteno', 'local_flavours'),
            1 => get_string('overwriteyes', 'local_flavours'));
        $mform->addElement('select', 'overwrite', $overwritelabel, $overwriteoptions);
        $mform->setType('overwrite', PARAM_INT);

        $mform->addElement('hidden', 'action', 'deployment_preview');
        $mform->addElement('submit', 'deployment_upload_submit', get_string('next'));
    }
}
