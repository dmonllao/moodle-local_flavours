<?php 

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/flavours/flavours_packaging.class.php');
require_once($CFG->dirroot . '/local/flavours/flavours_deployment.class.php');

$action = optional_param('action', 'packaging_form', PARAM_ALPHAEXT);

// Access control
require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));
// TODO: Add sesskey checking

// Calling the appropiate class
if (strstr($action, 'packaging') != false) {
    $classname = 'flavours_packaging';
} else {
    $classname = 'flavours_deployment';
}
$class = new $classname($action);

// Process the action
if (!method_exists($class, $action)) {
    print_error('actionnotsupported', 'local_flavours');
}
$class->process_wrapper();

// Output
echo $class->print_header();
echo $class->display();
echo $OUTPUT->footer();
