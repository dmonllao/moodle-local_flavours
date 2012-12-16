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
 * Parent main manager class implementation
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Common methods to manage the packaging and deployment of Moodle flavours
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class flavours {

    protected $action;
    protected $url;
    protected $flavourstmpfolder;
    protected $ingredienttypes;

    // Output
    protected $renderer;
    protected $renderable;

    public function __construct($action) {

        global $CFG;

        $this->action = $action;
        $this->url = $CFG->wwwroot.'/local/flavours/index.php';
        $this->flavourstmpfolder = $CFG->tempdir.'/flavours';

        // Temp directory may be absent on fresh installs
        if (!file_exists($CFG->tempdir)) {
            @mkdir ($CFG->tempdir . '/', $CFG->directorypermissions);
        }

        // Ensure that the flavours temp folder exists
        if (!file_exists($this->flavourstmpfolder)) {
            @mkdir ($this->flavourstmpfolder, $CFG->directorypermissions);
        }

        // Clean garbage caused by the packaging system or workflow exceptions
        $this->clean_garbage();

        // Getting the system ingredient types
        $this->set_ingredient_types();
    }


    /**
     * Creates the tree structure based on $this->ingredients
     *
     * @return array The tree html following the TreeView structure
     */
    protected function get_tree_ingredients() {

        global $PAGE;

        if (empty($this->ingredients)) {
            return false;
        }

        $treedata = '<ul>';
        foreach ($this->ingredients as $ingredienttype => $branch) {

            // A main ingredient/ namespace to ease the ingredients detection
            $prefix = 'ingredient/'.$ingredienttype;
            $this->get_tree_data($treedata, $branch, $prefix);
        }
        $treedata .= '</ul>';

        return $treedata;
    }


    /**
     * Gets the html to display the tree
     *
     * @param string $output
     * @param object $branch
     * @param string $prefix
     */
    protected function get_tree_data(&$output, $branch, $prefix) {

        $output .= '<li>';
        $output .= $branch->name;

        $output .= '<ul>';
        if (!empty($branch->branches)) {

            foreach ($branch->branches as $name => $data) {

                // To identify that branch/leaf and pass it through his branches
                $branchprefix = $prefix.'/'.$data->id;
                $branchnodeprefix = 'node_' . $branchprefix;

                // If it does not have children it's a leaf
                if (empty($data->branches)) {

                    $string = $data->name;
                    $title = $string;         // We set the title attribute

                    // Should we add restrictions info?
                    if (!empty($data->restrictions)) {

                        // A way to mark which ones have restrictions to work with TreeView
                        $title = '';

                        $string = '<span alt="'.$branchnodeprefix.'" class="error treenode">' . $string . ' - ';
                        $string .= $this->get_restrictions_string($data->restrictions);
                        $string .= '</span>';
                    } else {
                        $string = '<span alt="'.$branchnodeprefix.'" class="treenode" title="' . $title . '">' . $string . '</span>';
                    }

                    $output .= '<li>' . $string . '</li>';

                } else {
                    // Let's get the branch children
                    $this->get_tree_data($output, $data, $branchprefix);
                }
            }
        }

        $output .= '</ul>';
        $output .= '</li>';
    }


    /**
     * Returns a new instance of a ingredient_type
     *
     * @param string $type The ingredient type
     * @param flavours_ingredient $type
     */
    protected function instance_ingredient_type($type) {

        global $CFG;

        $classname = 'flavours_ingredient_'.$type;
        $filepath = $CFG->dirroot . '/local/flavours/ingredient/'.$classname.'.class.php';
        if (!file_exists($filepath)) {
            print_error('ingredienttypenotavailable', 'local_flavours');
        }

        // Getting the system ingredients of that type
        require_once($filepath);

        return new $classname();
    }


    /**
     * Centralized output
     * @return string The HTML to display
     */
    public function render() {

        global $PAGE;

        $renderer = $PAGE->get_renderer('local_flavours');

        return $renderer->render_flavours_wrapper($this->renderable, $this->action);
    }


    /**
     * Extracts the selected ingredients from $_POST
     * @return array The selected ingredients organized by ingredient type
     */
    public function get_ingredients_from_form() {

        if (empty($_POST)) {
            return false;
        }

        // Looking for selected ingredients and the ingredient type
        foreach ($_POST as $varname => $enabled) {

            if (strstr($varname, '/') == false) {
                continue;
            }

            $namespace = explode('/', $varname);
            if (array_shift($namespace) == 'ingredient') {

                $ingredienttype = array_shift($namespace);

                foreach ($namespace as $key => $value) {
                    $namespace[$key] = preg_replace('/[^0-9a-zA-Z_]/i', '', $value);
                }
                $ingredientpath = implode('/', $namespace);

                // Only organized by ingredient type, each type will
                // treat his ingredients on a different way
                $ingredients[$ingredienttype][$ingredientpath] = $ingredientpath;
            }
        }

        if (empty($ingredients)) {
            return false;
        }

        ksort($ingredients);

        return $ingredients;
    }


    /**
     * Sets the available ingredient types of the system
     *
     * Reads the flavours/ingredient/ folder to extract the ingredient names
     * from the available classes, excluding the parent class
     */
    protected function set_ingredient_types() {
        global $CFG;

        $this->ingredienttypes = array();

        $ingredientsdir = $CFG->dirroot . '/local/flavours/ingredient/';
        if ($dirhandler = opendir($ingredientsdir)) {
            while (($file = readdir($dirhandler)) !== false) {

                // Excluding the parent class (and maybe SCV hidden files or something like that)
                preg_match('/flavours_ingredient_(.*?).class.php/', $file, $ingredienttype);
                if ($ingredienttype) {
                    $this->ingredienttypes[] = $ingredienttype[1];
                }
            }
            closedir($dirhandler);
        }
    }


    /**
     * Recursive implementation of unlink() to remove directories
     *
     * @param string $path The path to remove
     * @return boolean
     */
    public function unlink($path) {

        if ($path == false || $path == '') {
            return false;
        }

        $status = true;

        if (!is_dir($path)) {
            $status = @unlink($path);

        } else {
            if (!$dir = opendir($path)) {
                return false;
            }
            while (false !== ($file = readdir($dir))) {
                if ($file == "." || $file == "..") {
                    continue;
                }

                $status = $status && $this->unlink($path . '/' . $file);
            }
            closedir($dir);

            $status = $status && @rmdir($path);
        }

        return $status;
    }


    /**
     * The restrictions in a string
     *
     * @param array $restrictions
     * @return string
     */
    protected function get_restrictions_string($restrictions) {

        $strs = array();
        if ($restrictions) {
            foreach ($restrictions as $restriction => $a) {

                if (strstr($restriction, 'warning') != false) {
                    $prefixstr = 'warning';
                } else {
                    $prefixstr = 'problem';
                }
                $strs[] = get_string($prefixstr, 'local_flavours') . ' ' .
                          get_string('restriction'.$restriction, 'local_flavours', $a);
            }
        }

        return implode(' / ', $strs);
    }


    /**
     * Deletes all the old moodledata/temp/flavours folders
     */
    protected function clean_garbage() {
        global $CFG;

        $olderthan = 3600;   // One hour
        $now = time();

        if (!$dir = opendir($this->flavourstmpfolder)) {
            return;
        }
        while (false !== ($file = readdir($dir))) {
            if ($file == "." || $file == "..") {
                continue;
            }

            $filepath = $this->flavourstmpfolder . '/' . $file;

            // If it's older than $olderthan remove it
            if ($now > (filemtime($filepath) + $olderthan)) {
                $this->unlink($filepath);
            }
        }
        closedir($dir);
    }
}


/**
 * Void function to pass to upgrade_plugins()
 */
function flavours_print_upgrade_void() {
	return;
}
