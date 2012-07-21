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
 * Flavours front controller
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/flavours/flavours_packaging.class.php');
require_once($CFG->dirroot . '/local/flavours/flavours_deployment.class.php');
require_once($CFG->dirroot . '/local/flavours/flavours_generatedefaults.class.php');
require_once($CFG->dirroot . '/local/flavours/lib.php');

$action = optional_param('action', 'packaging_form', PARAM_ALPHAEXT);

// Access control
require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

$context = get_context_instance(CONTEXT_SYSTEM);

$url = new moodle_url('/local/flavours/index.php');
$url->param('action', $action);
$PAGE->set_url($url);
$PAGE->set_context($context);

// Calling the appropiate class
$manager = substr($action, 0, strpos($action, '_'));
$classname = 'flavours_' . $manager;
$instance = new $classname($action);

$name = explode('_', $action);
$actualsettingspage = array_shift($name);
admin_externalpage_setup('local_flavours_'.$actualsettingspage);

// Process the action
if (!method_exists($instance, $action)) {
    print_error('actionnotsupported', 'local_flavours');
}
$instance->$action();

echo $instance->render();
