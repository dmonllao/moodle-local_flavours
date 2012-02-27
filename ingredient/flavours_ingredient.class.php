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
 * Class to specify the public interface of the ingredient classes
 *
 * It also contains common methods
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Abstract class to define the ingredients interface
 *
 * @abstract
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class flavours_ingredient {


    /**
     * Ingredient type id
     * @var string
     */
    public $id;


    /**
     * Name given to the ingredient type, to identify it on the ingredients tree
     * @var string
     */
    public $name;


    /**
     * The ingredient type ingredients
     * @var array
     */
    public $branches;


    /**
     * Gets an ingredients list with the ingredients availables on the system
     * @abstract
     */
    abstract public function get_system_info();


    /**
     * Stores the selected ingredients into the flavour folder
     *
     * @abstract
     * @param xml_writer $xmlwriter The XML writer, by reference
     * @param array $ingredients The ingredients to store
     * @param string $path Where to store the flavour tmp files
     * @return boolean Not treated but true if it adds something
     */
    abstract public function package_ingredients(&$xmlwriter, $ingredients, $path);


    /**
     * Gets an ingredients list with the ingredients availables on a flavour
     * @abstract
     * @param SimpleXMLElement $xml
     */
    abstract public function get_flavour_info($xml);


    /**
     * From the flavour to the system
     *
     * @abstract
     * @param array $ingredients What to deploy
     * @param string $path Path to the ingredient type file system
     * @param SimpleXMLElement $xml The XML of the ingredient type with the zip description
     * @return array Problems encountered during the ingredients deployment
     */
    abstract public function deploy_ingredients($ingredients, $path, SimpleXMLElement $xml);


    /**
     * Support function - copied from backup/lib.php and adapted to avoid SCV files
     *
     * @param string $from
     * @param string $to
     * @return boolean Feedback
     */
    protected function copy($from, $to) {

        global $CFG;

        if (!file_exists($from)) {
            return false;
        }

        // SCV systems to avoid
        $scvs = array('.git', 'CVS', '.svn');
        $scvsdirs = array_combine($scvs, $scvs);

        $status = true; // Initialize this, next code will change its value if needed

        if (is_file($from)) {
            umask(0000);
            if (!copy($from, $to)) {
                $status = false;
            } else {
                chmod($to, $CFG->directorypermissions);
                $status = true;
            }

        } else {

            if (!is_dir($to)) {
                umask(0000);
                if (!is_dir($to)) {
                    $status = mkdir($to, $CFG->directorypermissions);
                }
            }

            $dir = opendir($from);
            while (false !== ($file = readdir($dir))) {

                // We don't want SCVS files
                if ($file == "." || $file == ".." || !empty($scvsdirs[$file])) {
                    continue;
                }
                $status = $this->copy("$from/$file", "$to/$file");
            }
            closedir($dir);
        }

        return $status;
    }


    /**
     * Support function - Adapter to flavours->unlink
     *
     * @param string $path The path to delete
     * @return boolean
     */
    protected function unlink($path) {

        // All that dirty code to maintain 'flavours' as an abstract class
        // As a reminder, PHP > 5.2.? does not allow static methods of abstract classes
        $action = optional_param('action', 'packaging_form', PARAM_ALPHAEXT);
        $tmparray = explode('_', $action);
        $classname = 'flavours_' . $tmparray[0];
        $instance = new $classname($action);

        return $instance->unlink($path);
    }

}
