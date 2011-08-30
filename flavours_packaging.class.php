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
 * Flavours packaging system
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/filestorage/zip_packer.php');
require_once($CFG->dirroot . '/local/flavours/flavours_xml_writer.class.php');
require_once($CFG->dirroot . '/local/flavours/flavours_xml_transformer.class.php');
require_once($CFG->dirroot . '/backup/util/xml/output/xml_output.class.php');
require_once($CFG->dirroot . '/backup/util/xml/output/memory_xml_output.class.php');

require_once($CFG->dirroot . '/local/flavours/flavours.class.php');
require_once($CFG->dirroot . '/local/flavours/forms/flavours_packaging_form.php');

/**
 * Packaging system manager
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_packaging extends flavours {

    /**
     * Outputs the packaging form
     */
    public function packaging_form() {

        global $CFG, $PAGE;

        // Getting the ingredient types data
        foreach ($this->ingredienttypes as $type) {

            // instnace_ingredient_type get a new flavours_ingredient_* object
            $this->ingredients[$type] = $this->instance_ingredient_type($type);
            $this->ingredients[$type]->get_system_info();
        }

        // Initializing the tree
        $PAGE->requires->js_init_call('M.local_flavours.init', null, true);

        // And rendering on dom ready
        $PAGE->requires->js_init_call('M.local_flavours.render', null, true);

        // Fill the ingredients tree with this->ingredients (ondomready)
        $customdata['treedata'] = $this->get_tree_ingredients();

        $this->renderable = new flavours_packaging_form($this->url, $customdata);
    }


    /**
     * Packages the entire flavour and returns it
     * @todo Add a checksum
     */
    public function packaging_execute() {

        global $USER, $CFG;

        $errorredirect = $this->url . '?sesskey=' . sesskey();

        // Getting selected data
        $selectedingredients = $this->get_ingredients_from_form();
        if (!$selectedingredients) {
            redirect($errorredirect, get_string('nothingselected', 'local_flavours'), 2);
        }

        // Flavour data
        $form = new flavours_packaging_form($this->url);
        if (!$data = $form->get_data()) {
            print_error('errorpackaging', 'local_flavours');
        }

        // Starting <xml>
        $xmloutput = new memory_xml_output();
        $xmltransformer = new flavours_xml_transformer();
        $xmlwriter = new flavours_xml_writer($xmloutput, $xmltransformer);
        $xmlwriter->start();
        $xmlwriter->begin_tag('flavour');
        $xmlwriter->full_tag('name', $data->name);
        $xmlwriter->full_tag('description', $data->description);
        $xmlwriter->full_tag('author', $data->author);
        $xmlwriter->full_tag('timecreated', time());
        $xmlwriter->full_tag('sourceurl', $CFG->wwwroot);
        $xmlwriter->full_tag('sourcemoodlerelease', $CFG->release);
        $xmlwriter->full_tag('sourcemoodleversion', $CFG->version);

        // Random code to store the flavour data
        $hash = sha1('flavour_'.$USER->id.'_'.time());
        $flavourpath = $this->flavourstmpfolder.'/'.$hash;

        if (file_exists($flavourpath) || !mkdir($flavourpath, $CFG->directorypermissions)) {
            print_error('errorpackaging', 'local_flavours');
        }

        // Adding the selected ingredients data
        $xmlwriter->begin_tag('ingredient');
        foreach ($selectedingredients as $ingredienttype => $ingredientsdata) {

            // instance_ingredient_type gets a new flavours_ingredient_* object
            $type = $this->instance_ingredient_type($ingredienttype);

            $xmlwriter->begin_tag($type->id);

            // It executes the ingredient type specific actions to package
            $type->package_ingredients($xmlwriter, $flavourpath, $ingredientsdata);

            $xmlwriter->end_tag($type->id);
        }
        $xmlwriter->end_tag('ingredient');

        // Finishing flavour index
        $xmlwriter->end_tag('flavour');
        $xmlwriter->stop();
        $flavourxml = $xmloutput->get_allcontents();

        // Creating the .xml with the flavour info
        $xmlfilepath = $flavourpath . '/flavour.xml';
        if (!$xmlfh = fopen($xmlfilepath, 'w')) {
            print_error('errorpackaging', 'local_flavours');
        }
        fwrite($xmlfh, $flavourxml);
        fclose($xmlfh);

        // Flavour contents compression
        $packer = new zip_packer();
        $zipfilepath = $this->flavourstmpfolder .
            '/' . $hash . '/flavour_' . date('Y-m-d') . '.zip';
        if (!$packer->archive_to_pathname(array('flavour' => $flavourpath), $zipfilepath)) {
            print_error('errorpackaging', 'local_flavours');
        }

        session_get_instance()->write_close();
        send_file($zipfilepath, basename($zipfilepath));

        // To avoid the html headers and all the print* stuff
        die();
    }
}
