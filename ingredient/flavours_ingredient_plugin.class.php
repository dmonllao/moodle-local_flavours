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
 * Plugin ingredient type
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/lib/pluginlib.php');
require_once($CFG->dirroot . '/lib/upgradelib.php');
require_once($CFG->dirroot . '/local/flavours/ingredient/flavours_ingredient.class.php');

/**
 * Manages the packaging and deployment of all the moodle plugins
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_ingredient_plugin extends flavours_ingredient {


    /**
     * Sets the ingredient name and identifier
     */
    public function __construct() {
        $this->id = 'plugin';
        $this->name = get_string('plugin');
    }


    /**
     * Gets the list of plugin types and the system available ingredients
     */
    public function get_system_info() {

        // Load moodle plugins manager and get the plugins
        $pluginman = plugin_manager::instance();
        $pluginman->get_plugins();
        $pluginman->get_subplugins();

        // Getting the plugin types
        $plugintypes = get_plugin_types();

        foreach ($plugintypes as $type => $path) {

            $plugins = get_plugin_list($type);

            // We only add the plugin type if it has plugins
            if ($plugins) {

                // Core plugins
                if ($coreplugins = $pluginman->standard_plugins_list($type)) {
                    $coreplugins = array_combine($coreplugins, $coreplugins);
                }

                // The plugin type data
                $branchid = $type;
                $branchname = $pluginman->plugintype_name_plural($type);

                foreach ($plugins as $pluginname => $pluginpath) {

                    // We will only list the non standard plugins
                    if (!empty($coreplugins) && !empty($coreplugins[$pluginname])) {
                        continue;
                    }

                    $this->branches[$type]->branches[$pluginname] = new StdClass();
                    $this->branches[$type]->branches[$pluginname]->id = $pluginname;

                    // The plugin user friendly name
                    $pluginvisiblename = $this->get_system_plugin_visiblename($type, $pluginname);
                    $this->branches[$type]->branches[$pluginname]->name = $pluginvisiblename;
                }

                // Only if there is non core plugins
                if (empty($this->branches[$type]->branches)) {
                    continue;
                }

                $this->branches[$type]->id = $branchid;
                $this->branches[$type]->name = $branchname;
            }
        }
    }


    /**
     * Copies the selected plugins to the flavour file structure
     *
     * @param xml_writer $xmlwriter The XML writer, by reference
     * @param string $path Where to store the data
     * @param array $ingredientsdata Ingredients to store
     */
    public function package_ingredients(&$xmlwriter, $path, $ingredientsdata) {

        global $CFG;

        if (!$ingredientsdata) {
            return false;
        }

        // Required to find plugin types paths
        $plugintypesdata = get_plugin_types();

        // To find the plugins versions
        $pluginman = plugin_manager::instance();
        $systemplugins = $pluginman->get_plugins(true);

        mkdir($path . '/' . $this->id, $CFG->directorypermissions);

        // A first iteration to group ingredients by plugin type
        foreach ($ingredientsdata as $plugintype) {
            $tmparray = explode('/', $plugintype);
            $plugins[$tmparray[0]][$tmparray[1]] = $tmparray[1];
        }

        foreach ($plugins as $plugintype => $ingredients) {

            $xmlwriter->begin_tag($plugintype);

            // The plugin type folder
            $plugintypepath = $plugintypesdata[$plugintype];
            $plugintypebasepath = str_replace($CFG->dirroot, '', $plugintypepath);
            $plugintypeflavourpath = str_replace(
                rtrim($CFG->dirroot, '/'),
                $path . '/' . $this->id,
                $plugintypepath);

            // First condition to avoid subplugins conflicts
            if (!is_dir($plugintypeflavourpath)) {
                if (!mkdir($plugintypeflavourpath, $CFG->directorypermissions, true)) {
                    debugging($plugintypeflavourpath);
                    continue;
                }
            }

            foreach ($ingredients as $ingredient) {

                // Copying to the flavour filesystem
                $frompath = $plugintypepath . '/' . $ingredient;

                // Recursive copy
                $topath = $plugintypeflavourpath . '/' . $ingredient;
                if (!$this->copy($frompath, $topath)) {
                    debugging($frompath . '---' . $topath);
                    print_error('errorcopying', 'local_flavours');
                }

                // Adding the ingredient to the flavour data
                $xmlwriter->begin_tag($ingredient);
                $xmlwriter->full_tag('name',
                                    $this->get_system_plugin_visiblename($plugintype, $ingredient));
                $xmlwriter->full_tag('path',
                                      ltrim($plugintypebasepath, '/') . '/' . $ingredient);

                // The plugin version and required moodle version
                if (!empty($systemplugins[$plugintype][$ingredient]->versionrequires)) {
                    $requires = $systemplugins[$plugintype][$ingredient]->versionrequires;
                } else {
                    $requires = '';
                }
                if (!empty($systemplugins[$plugintype][$ingredient]->versiondisk)) {
                    $version = $systemplugins[$plugintype][$ingredient]->versiondisk;
                } else {
                    $version = '';
                }

                $xmlwriter->full_tag('versiondisk', $version);
                $xmlwriter->full_tag('requires', $requires);
                $xmlwriter->end_tag($ingredient);
            }

            $xmlwriter->end_tag($plugintype);
        }

        return true;
    }


    /**
     * Lists the flavour plugins
     * @param SimpleXMLElement $xml
     */
    public function get_flavour_info($xml) {
        global $CFG;

        $overwrite = required_param('overwrite', PARAM_INT);

        $pluginman = plugin_manager::instance();
        $systemplugins = $pluginman->get_plugins();
        $plugintypespaths = get_plugin_types();

        $ingredients = $xml->children();
        foreach ($ingredients as $plugintype => $plugins) {

            unset($nowritable);

            // Writable directory?
            $dir = $plugintypespaths[$plugintype];
            if (!is_writable($dir)) {
                $nowritable = true;
            }

            $this->branches[$plugintype] = new stdClass();

            foreach ($plugins as $pluginname => $plugindata) {


                $this->branches[$plugintype]->branches[$pluginname] = new stdClass();

                // Only to display on notifications
                $pluginfull = $plugintype . '/' . $pluginname;

                // Versioning checkings
                if (!empty($systemplugins[$plugintype][$pluginname])) {

                    $systemplugin = $systemplugins[$plugintype][$pluginname];

                    $pluginversiondisk = (String) $plugindata->versiondisk;
                    $pluginrequires = (String) $plugindata->requires;

                    // Overwrite disabled
                    if (!empty($systemplugin) && !$overwrite) {
                        $this->branches[$plugintype]->branches[$pluginname]->restrictions['pluginalreadyinstalled'] = $pluginfull;
                    }

                    // If the flavour plugin doesn't have a versiondisk (filters for example)
                    // don't overwrite (and skip notification if the upper one has been displayed
                    if (empty($pluginversiondisk) &&
                        empty($this->branches[$plugintype]->branches[$pluginname]->restrictions)) {
                        $this->branches[$plugintype]->branches[$pluginname]->restrictions['pluginnoversiondiskupgrade'] = $pluginfull;
                    }

                    // Overwrite if newer release on flavour
                    if (!empty($systemplugin) && $overwrite && !empty($pluginversiondisk) &&
                        $pluginversiondisk <= $systemplugin->versiondisk) {

                        $this->branches[$plugintype]->branches[$pluginname]->restrictions['pluginflavournotnewer'] = $pluginfull;
                    }

                }

                // Required Moodle version to use the plugin
                if (!empty($pluginrequires) && $CFG->version < $pluginrequires) {
                    $this->branches[$plugintype]->branches[$pluginname]->restrictions['pluginsystemold'] = $pluginfull;
                }

                $this->branches[$plugintype]->id = $plugintype;
                $this->branches[$plugintype]->name = $pluginman->plugintype_name_plural($plugintype);
                $this->branches[$plugintype]->branches[$pluginname]->id = $pluginname;
                $this->branches[$plugintype]->branches[$pluginname]->name = (String)$plugindata->name;

                if (!empty($nowritable)) {
                    $this->branches[$plugintype]->branches[$pluginname]->restrictions['pluginnowritable'] = $dir;
                }
            }

        }
    }


    /**
     * Adds and upgrades the selected plugins
     *
     * @param array $ingredients
     * @param string $path Path to the ingredient type file system
     * @param SimpleXMLElement $xml
     * @return array Problems during the ingredients deployment
     */
    public function deploy_ingredients($ingredients, $path, SimpleXMLElement $xml) {

        // Using the $ingredients array keys to maintain coherence with the main deployment method
        $problems = array();

        $pluginman = plugin_manager::instance();
        $plugintypespaths = get_plugin_types();

        $this->get_flavour_info($xml);

        foreach ($ingredients as $selection) {

            // [0] => ingredienttype, [1] => ingredientname
            $ingredientdata = explode('/', $selection);
            $type = $ingredientdata[0];
            $ingredient = $ingredientdata[1];

            if (empty($this->branches[$type]->branches[$ingredient])) {
                $problems[$selection]['pluginnotfound'] = $selection;
                continue;
            }
            $ingredientdata = $this->branches[$type]->branches[$ingredient];

            // Adapter to the restrictions array
            if (!empty($ingredientdata->restrictions)) {
                $problems[$selection] = $ingredientdata->restrictions;
                continue;
            }

            if (empty($xml->{$type}) || empty($xml->{$type}->{$ingredient})) {
                $problems[$selection]['pluginnotfound'] = $selection;
                continue;
            }

            // Deploy then
            $ingredientpath = $plugintypespaths[$type] . '/' . $ingredient;

            // Remove old dir if present
            if (file_exists($ingredientpath)) {

                // Report if the old plugin directory can't be removed
                if (!$this->unlink($ingredientpath)) {
                    $problems[$selection]['plugincantremove'] = $selection;
                    continue;
                }
            }

            // Copy the new contents where the flavour says
            $tmppath = $path . '/' . $xml->{$type}->{$ingredient}->path;
            if (!$this->copy($tmppath, $ingredientpath)) {
                debugging('From : ' . $tmppath . ' To: ' . $ingredientpath);
                $problems[$selection]['plugincopyerror'] = $selection;
            }
        }

        // Execute the moodle upgrade process
        try {
            foreach ($plugintypespaths as $type => $location) {
                upgrade_plugins($type, 'flavours_print_upgrade_void', 'flavours_print_upgrade_void', false);
            }
        } catch (Exception $ex) {
            abort_all_db_transactions();
            $info = get_exception_info($ex);
            upgrade_log(UPGRADE_LOG_ERROR, $ex->module, 'Exception: ' . get_class($ex), $info->message, $info->backtrace);
        }

        return $problems;
    }


    /**
     * Returns (if possible) the visible name of the plugin
     *
     * Not all the Moodle plugins follows the "pluginname" convention so let's
     * display the plugin string identifier instead of the human readable pluginname
     *
     * @param string $plugintype
     * @param string $pluginname
     * @return string The name to show
     */
    private function get_system_plugin_visiblename($plugintype, $pluginname) {

        $component = $plugintype.'_'.$pluginname;
        if (!get_string_manager()->string_exists('pluginname', $component)) {
            $pluginvisiblename = $pluginname;
        } else {
            $pluginvisiblename = get_string('pluginname', $component);
        }

        return $pluginvisiblename;
    }

}
