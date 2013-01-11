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
 * Settings ingredient type
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/flavours/ingredient/flavours_ingredient.class.php');


/**
 * Manages the packaging and deployment of administration settings
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_ingredient_setting extends flavours_ingredient {

    /**
     * For the settings with more than one value
     *
     * The extra values will be attributes of the "main" setting, using the CFG setting name
     * to ease the deployment.
     * @var array
     */
    private $multiplevaluemapping = array('fix' => '_adv', 'adv' => '_adv', 'locked' => '_locked');

    /**
     * Manages the warnings output
     *
     * There should be a warning on the preview screen but the execution results
     * should display feedback. deploy_ingredients() sets it and get_flavour_branches()
     * treats it
     *
     * @var bool
     */
    private $displaywarnings = true;

    /**
     * Sets the ingredient name and identifier
     */
    public function __construct() {
        $this->id = 'setting';
        $this->name = get_string('settings');
    }


    /**
     * Gets the admin tree instance and populates $this->branch
     * with the admin tree categories and pages
     */
    public function get_system_info() {
        $adminroot = admin_get_root();
        $this->get_branch_settings($adminroot->children, $this);
    }


    /**
     * Writes the xml with the selected admin settings
     *
     * @param xml_writer $xmlwriter The XML writer, by reference
     * @param string $path Where to store the data
     * @param array $ingredientsdata Settings to store
     */
    public function package_ingredients(&$xmlwriter, $path, $ingredientsdata) {

        if (!$ingredientsdata) {
            return false;
        }

        $adminroot = admin_get_root();
        $this->get_branch_settings($adminroot->children, $this, true);

        foreach ($ingredientsdata as $settingspage) {

            // Settings page path
            $namespace = explode('/', $settingspage);

            // The admin settingspage is the last one
            $page = array_pop($namespace);

            if (!$settings = $this->get_settingspage_settings($namespace, $page, $this)) {
                continue;
            }

            $settingspagetagname = str_replace('/', '.', $settingspage);
            $xmlwriter->begin_tag($settingspagetagname);

            // Adding settings
            foreach ($settings as $setting) {

                // Getting the attributes of the tag
                // Some plugins has slashes, not availables as part of the tag name
                $attrs = array('plugin' => str_replace('/', '.', $setting->plugin));

                // Adding the extra values of the setting (if present) to the attributes array
                if (!empty($setting->attrs)) {
                    $attrs = array_merge($attrs, $setting->attrs);
                    $attrs['hasextra'] = '1';
                }

                $xmlwriter->full_tag($setting->name,
                    $this->get_setting_value($setting->name, $setting->plugin),
                    $attrs);
            }

            $xmlwriter->end_tag($settingspagetagname);
        }

        return true;
    }


    /**
     * Obtains the settings pages availables on the flavour
     *
     * @todo Think of other ways to get the visiblename without so much trouble!
     * @param SimpleXMLElement $xml
     */
    public function get_flavour_info($xml) {

        // Getting all the system settings to verify the available settings pages and to
        // get the real visiblename of the flavour settings pages and settings categories
        $adminroot = admin_get_root();
        $this->get_branch_settings($adminroot->children, $systemsettings);

        foreach ($xml as $namespace => $settings) {

            // Converting from the xml settingspage namespace to the tree branches format
            $treepath = explode('.', $namespace);

            // Recursive call to get all the settings pages
            $this->get_flavour_branches($treepath, $this->branches, $systemsettings->branches);
        }

    }


    /**
     * Sets the settings values
     *
     * @param array $ingredients
     * @param string $path Path to the ingredient type file system
     * @param SimpleXMLElement $xml
     * @return array Problems during the ingredients deployment
     */
    public function deploy_ingredients($ingredients, $path, SimpleXMLElement $xml) {

        $problems = array();

        // We don't want warnings, we want real feeback
        $this->displaywarnings = false;

        $xmlingredients = $xml->children();

        foreach ($ingredients as $ingredient) {

            $xmlingredient = str_replace('/', '.', $ingredient);

            if (empty($xmlingredients->$xmlingredient)) {
                $problems[$ingredient]['settingnosettingpage'] = $xmlingredient;
                continue;
            }

            // Getting settings and overwritting
            $pagesettings = $xmlingredients->$xmlingredient->children();

            $settingsproblemsarray = array();
            foreach ($pagesettings as $settingname => $settingdata) {

                $plugin = (String) $settingdata->attributes()->plugin;
                if (!$plugin) {
                    $settingsproblemsarray[$ingredient][] = $settingname;
                    continue;
                }

                if ($plugin == 'core') {
                    $plugin = null;
                }

                // Restoring the slashes removed on packaging
                $plugin = str_replace('.', '/', $plugin);

                set_config($settingname, (String) $settingdata[0], $plugin);

                // If it's a setting with multiple values set them
                if (!empty($settingdata->attributes()->hasextra)) {
                    $attrs = $settingdata->attributes()->hasextra;
                    foreach ($attrs as $key => $value) {

                        $value = (String) $value;
                        if ($key != 'hasextra' && $key != 'plugin') {
                            set_config($key, $value, $plugin);
                        }
                    }
                }
            }
        }

        // Imploding settings of the same settings page with problems
        foreach ($settingsproblemsarray as $ingredient => $settingsarray) {
            $problems[$ingredient]['settingsettingproblems'] = implode(', ', $settingsarray);
        }

        return $problems;
    }


    /**
     * Gets a string with PHP code to inject default values to the upgrade processes
     *
     * @todo Try to respect DRY and unify part of package_ingredients with settings_to_php
     * @uses flavours_php_writer
     * @param array $selectedsettings
     * @returns array PHP code, one line per array element, the caller will make a string or a file
     */
    public function settings_to_php($selectedsettings) {

        $phparray = array();

        $adminroot = admin_get_root();
        $this->get_branch_settings($adminroot->children, $this, true);

        foreach ($selectedsettings as $settingspage) {

            // Settings page path
            $namespace = explode('/', $settingspage);

            // The admin settingspage is the last one
            $page = array_pop($namespace);

            if (!$settings = $this->get_settingspage_settings($namespace, $page, $this)) {
                continue;
            }

            $settingspagetagname = str_replace('/', '.', $settingspage);

            // Adding settings
            foreach ($settings as $setting) {

                // Can\'t refactor the whole 'core' code to 'moodle' to maintain
                // backward compatibility with flavours packages
                if ($setting->plugin == 'core') {
                    $setting->plugin = 'moodle';
                }

                // Restore slashes
                $setting->plugin = str_replace('.', '/', $setting->plugin);

                // Valu with addslashes only in the single qoute
                $value = $this->get_setting_value($setting->name, $setting->plugin);
                $value = str_replace("'", "\'", $value);

                $phparray[] = $this->add_setting_php($setting->plugin, $setting->name, $value);

                // Adding the extra values of the setting (if present) to the attributes array
                if (!empty($setting->attrs)) {
                    foreach ($setting->attrs as $attrname => $value) {
                        $phparray[] = $this->add_setting_php($setting->plugin, $attrname, $value);
                    }
                }
            }

        }

        return $phparray;
    }


    /**
     * Iterates through the moodle admin tree to extract the settings categories & pages hierarchy
     *
     * @param object $admintreebranch
     * @param object $branch
     * @param boolean $addsettings Should settings be included?
     */
    protected function get_branch_settings($admintreebranch, &$branch, $addsettings = false) {

        foreach ($admintreebranch as $key => $child) {

            // Adding settings category and it's children
            if (is_a($child, 'admin_category')) {

                if ($child->children) {
                    $branch->branches[$child->name] = new StdClass();
                    $branch->branches[$child->name]->id = $child->name;
                    $branch->branches[$child->name]->name = $child->visiblename;

                    // Adding branch branches
                    $this->get_branch_settings($child->children, $branch->branches[$child->name],
                        $addsettings);
                }

            } else if (is_a($child, 'admin_settingpage') && $child->settings) {
                // Adding the settings pages if we find settings
                $branch->branches[$child->name] = new StdClass();
                $branch->branches[$child->name]->id = $child->name;
                $branch->branches[$child->name]->name = $child->visiblename;

                // Adding the settings if required
                if ($addsettings && !empty($child->settings)) {
                    foreach ($child->settings as $settingname => $setting) {

                        $branch->branches[$child->name]->settings[$settingname] = new stdClass();

                        if ($setting->plugin == '') {
                            $branch->branches[$child->name]->settings[$settingname]->plugin = 'core';
                        } else {

                            // Some plugins has slashes, not availables as part of the tag name
                            $setting->plugin = str_replace('/', '.', $setting->plugin);
                            $branch->branches[$child->name]->settings[$settingname]->plugin = $setting->plugin;
                        }

                        // Setting value
                        $branch->branches[$child->name]->settings[$settingname]->name = $settingname;

                        // Look for (.*?)_adv settings and add them as attributes
                        if (is_array($setting->defaultsetting)) {
                            foreach ($setting->defaultsetting as $key => $value) {

                                // Value is the name of the "main" value
                                if ($key != 'value' && isset($this->multiplevaluemapping[$key])) {
                                    $cfgkey = $settingname . $this->multiplevaluemapping[$key];
                                    $branch->branches[$child->name]->settings[$settingname]->attrs[$cfgkey] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Returns all the settings of a settingspage
     *
     * @param array $settingspagenamespace The path of the settingspage inside the admin tree
     * @param string $settingspage The name of the settings page
     * @param admin_tree $settingpagebranch Where to search
     * @return array An array of admin_settings
     */
    protected function get_settingspage_settings($settingspagenamespace, $settingspage, $settingpagebranch) {

        // Iteration through the namespace to locate the settingspage and get it's settings
        foreach ($settingspagenamespace as $settingscategory) {
            if (empty($settingpagebranch->branches[$settingscategory])) {
                print_error('errorunknownsettingspage', 'local_flavours', $settingspage);
            }
            $settingpagebranch = $settingpagebranch->branches[$settingscategory];
        }

        // It there aren't available settings skip it
        if (empty($settingpagebranch->branches[$settingspage]->settings)) {
            return false;
        }

        return $settingpagebranch->branches[$settingspage]->settings;
    }


    /**
     * Gets the setting value
     *
     * @todo Take into account site name and things like that which are not stored on config* tables
     * @param string $name
     * @param string $plugin
     * @return mixed
     */
    protected function get_setting_value($name, $plugin) {
        return get_config($plugin, $name);
    }


    /**
     * Recursive method to fill the array with all the settings pages and with the common format
     *
     * It checks if there is an available settingpage on the system for each flavour settingpage
     * @param array $treepath An array containing the path to the settingpage
     * @param array $branch Where to put the resulting data
     * @param array $systemsettings To check the settingpage existence and to get the visiblename
     */
    protected function get_flavour_branches($treepath, &$branch, $systemsettings) {

        $node = array_shift($treepath);

        if (empty($branch[$node])) {
            $branch[$node] = new stdClass();
        }
        $branch[$node]->id = $node;

        // Checking the existence of the settingpage on this moodle release
        if (empty($systemsettings[$node])) {

            $branch[$node]->restrictions['settingnosettingpage'] = true;

            // Can't be shown if we leave the name empty
            $branch[$node]->name = $node;
            return false;
        }

        // Display warnings, basically on preview
        if ($this->displaywarnings) {
            $branch[$node]->restrictions['settingwarningoverwrite'] = true;
        }

        $branch[$node]->name = $systemsettings[$node]->name;

        // Continue reaching the settings page
        if (!empty($treepath)) {
            $this->get_flavour_branches($treepath, $branch[$node]->branches, $systemsettings[$node]->branches);
        }

    }


    /**
     * Prepares a string in PHP representing a array[][]
     * @param string $plugin
     * @param string $name
     * @param mixed $value
     * @return string The PHP to add to the list
     */
    protected function add_setting_php($plugin, $name, $value) {
        return '$defaults[\'' . $plugin . '\'][\'' . $name . '\'] = \'' . $value . '\';';
    }


    /**
     * Get all the branches nodes as form elements names
     *
     * Used to force the selection of all the elements without a prev form selection
     * Uses the same format get by flavours->get_ingredients_from_form()
     *
     * @return array Array with all the settings
     */
    public function get_all_nodes($branches = false, $prefix = '', & $returnarray) {

        // Initialize the search
        if (!$branches) {
            $branches = $this->branches;
        }

        foreach ($branches as $name => $branchdata) {

            // Iterate recursively
	        if (!empty($branchdata->branches)) {
	            $this->get_all_nodes($branchdata->branches, $prefix . $branchdata->id . '/', $returnarray);

            // Then let's add it's children
	        } else {
                $settingkey = $prefix . $branchdata->id;
                $returnarray['setting'][$settingkey] = $settingkey;
	        }
        }

        return $returnarray;
    }

}
