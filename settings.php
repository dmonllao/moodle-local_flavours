<?php

defined('MOODLE_INTERNAL') || die;

$flavoursplugin = 'local_flavours';
$flavourspath = '/local/flavours/index.php';

$ADMIN->add('server', new admin_externalpage($flavoursplugin.'_packaging',
    get_string('package', $flavoursplugin),
    new moodle_url($flavourspath)));

$ADMIN->add('server', new admin_externalpage($flavoursplugin.'_deployment',
    get_string('deploy', $flavoursplugin),
    new moodle_url($flavourspath.'?action=deployment_upload')));
    