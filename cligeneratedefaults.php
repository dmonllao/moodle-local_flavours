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
 * This script creates config.php file and prepares database.
 *
 * This script is not intended for beginners!
 * Potential problems:
 * - environment check is not present yet
 * - su to apache account or sudo before execution
 * - not compatible with Windows platform
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2012 David Monllaó <david.monllao@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');       // various admin-only functions
require_once($CFG->libdir.'/clilib.php');         // cli only functions
require_once($CFG->libdir.'/environmentlib.php');

require_once($CFG->dirroot . '/local/flavours/lib.php');
require_once($CFG->dirroot . '/local/flavours/flavours_generatedefaults.class.php');
require_once($CFG->dirroot . '/local/flavours/ingredient/flavours_ingredient_setting.class.php');


// now get cli options
list($options, $unrecognized) = cli_get_params(
    array(
        'help'               => false,
        'overwrite-defaults' => false
    ),
    array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Command line Moodle default system settings updater.
Please note you must execute this script with the same uid as apache!

Options:
--overwrite-defaults     Overwrite local/defaults.php
-h, --help               Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/flavours/cligeneratedefaults.php
"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

$overwrite = false;
if (!empty($options['overwrite-defaults'])) {
    $overwrite = true;
}

// Logged as admin to get the full admin tree
$admins = get_admins();
$admin = reset($admins);
session_set_user($admin);

// Get all system settings (like selecting from flavours packaging form)
$settingsingredient = new flavours_ingredient_setting();
$settingsingredient->get_system_info();

// Formatting the branches as selected nodes
$returnarray = array();
$selectedsettings = $settingsingredient->get_all_nodes(false, '', $returnarray);


// Execute
$generator = new flavours_generatedefaults('generatedefaults_execute');
$generator->generatedefaults_execute($overwrite, $selectedsettings);


if (!$overwrite) {
    echo get_string('clinooverwrite', 'local_flavours') . "\n";
} else {
    echo get_string('clifinished', 'local_flavours')."\n";
}

exit(0); // 0 means success
