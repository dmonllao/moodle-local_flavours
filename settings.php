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
 * To add the flavours links to the administration block
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$flavoursplugin = 'local_flavours';
$flavoursurl = '/local/flavours/index.php?sesskey=' . sesskey();

$ADMIN->add('server', new admin_externalpage($flavoursplugin.'_packaging',
    get_string('package', $flavoursplugin),
    new moodle_url($flavoursurl)));

$ADMIN->add('server', new admin_externalpage($flavoursplugin.'_deployment',
    get_string('deploy', $flavoursplugin),
    new moodle_url($flavoursurl . '&action=deployment_upload')));

$ADMIN->add('server', new admin_externalpage($flavoursplugin.'_generatedefaults',
    get_string('generatedefaults', $flavoursplugin),
    new moodle_url($flavoursurl . '&action=generatedefaults_form')));

    