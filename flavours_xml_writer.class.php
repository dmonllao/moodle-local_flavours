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
 * A simple extension of the moodle xml_writer
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/backup/util/xml/xml_writer.class.php');

/**
 * Extending the xml_writer base to avoid CDATA tags reformat
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_xml_writer extends xml_writer {


    /**
     * Just to avoid the parent method execution
     *
     * The parent method cleans < and > to avoid XML parsing problems
     * but flavours implements an xml_transformer to wrap all the non-numeric
     * & non-null values in a CDATA tag, so the < and > cleaning is not necessary
     * @param string $content
     * @return string
     */
    protected function xml_safe_text_content($content) {
        return $content;
    }
}
