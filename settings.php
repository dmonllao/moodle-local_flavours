<?php

defined('MOODLE_INTERNAL') || die;

$flavoursplugin = 'local_flavours';
$flavoursurl = '/local/flavours/index.php?sesskey=' . sesskey();

$ADMIN->add('server', new admin_externalpage($flavoursplugin.'_packaging',
    get_string('package', $flavoursplugin),
    new moodle_url($flavoursurl)));

$ADMIN->add('server', new admin_externalpage($flavoursplugin.'_deployment',
    get_string('deploy', $flavoursplugin),
    new moodle_url($flavoursurl . '&action=deployment_upload')));
    