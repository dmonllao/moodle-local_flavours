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
 * Customized languages ingredient type
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/flavours/ingredient/flavours_ingredient_lang.class.php');


/**
 * Manages the packaging and deployment of customized languages
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_ingredient_customlang extends flavours_ingredient_lang {


    /**
     * Sets the ingredient name and identifier
     */
    public function __construct() {
        global $CFG;

        $this->id = 'customlang';
        $this->name = get_string('localstringcustomization', 'admin');

        $this->langpath = rtrim($CFG->langlocalroot, '/') . '/';
    }


    /**
     * Gets the language packs with modified strings
     */
    public function get_system_info() {

        // System language customizations
        $customs = $this->get_system_customlangs();

        // Get all the system languages
        parent::get_system_info();

        // Remove the ones without local modifications
        if (!empty($this->branches)) {
            foreach ($this->branches as $langid => $data) {

                $customlangdir = $langid . '_local';
                if (empty($customs[$customlangdir])) {
                    unset($this->branches[$langid]);
                }
            }
        }

        // Adding 'en' if necessary, which is avoided when getting the system info
        $id = 'en';
        if (!empty($customs[$id . '_local'])) {

            // To obtain the name
            $string = get_string_manager()->load_component_strings('langconfig', $id);

            $this->branches[$id]->id = $id;
            $this->branches[$id]->name = $string['thislanguage'] . ' ('. $id .')';
        }
    }


    /**
     * Gets the custom languages availables on the flavour
     *
     * It also loads $this->restrictions with ->general and ->specific attributes (array type both).
     * The array key will be used to get the language string
     *
     * @param SimpleXMLElement $xml
     */
    public function get_flavour_info($xml) {
        global $CFG;

        $systemlangs = get_string_manager()->get_list_of_translations();
        $alllangs = get_string_manager()->get_list_of_languages();

        // System language customizations
        $customs = $this->get_system_customlangs();

        // File permissions
        $langsfolder = $CFG->dataroot.'/lang/';
        if (!is_writable($langsfolder)) {
            $nowritable = true;
        }

        $ingredients = $xml->children();
        if (!$ingredients) {
            return false;
        }

        foreach ($ingredients as $lang => $langdata) {

            // Writable directory?
            if (!empty($nowritable)) {
                $this->branches[$lang]->restrictions['langfilepermissions'] = $langsfolder;
            }

            // Installed language?
            // Commented to avoid problem when deploying customlangs before langs
            //if (empty($systemlangs[$lang])) {
            //    $this->branches[$lang]->restrictions['customlangnotinstalled'] = $lang;
            //}

            // Custom strings already created?
            if (!empty($customs[$lang . '_local'])) {
                $this->branches[$lang]->restrictions['customlangalreadycreated'] = $lang;
            }

            // Valid language?
            if (empty($alllangs[$lang])) {
                $this->branches[$lang]->restrictions['langnotvalid'] = $lang;

            }

            $this->branches[$lang]->id = $lang;
            $this->branches[$lang]->name = (String) $langdata->name;
        }
    }


    /**
     * Returns the name of the dir containing the modified strings
     *
     * @param string $langid
     * @return string
     */
    protected function get_lang_dir($langid) {
        return $langid . '_local';
    }


    /**
     * Returns the list of languages with customizations
     *
     * @return array An associative array with the filename as key
     */
    protected function get_system_customlangs() {

        $customs = array();

        if (!$dir = @opendir($this->langpath)) {
            return false;
        }

        // Iterate through the langs folder to get language customizations
        while (false !== ($file = readdir($dir))) {
            if ($file == "." || $file == ".." || strstr($file, '_local') === false) {
                continue;
            }

            $customs[$file] = $file;
        }
        closedir($dir);

        return $customs;
    }

}
