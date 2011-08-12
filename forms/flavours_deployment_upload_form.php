<?php

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Form to upload a flavour
 * 
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_deployment_upload_form extends moodleform {


    function definition () {

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
