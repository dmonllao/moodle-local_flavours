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
 * The flavours XML transformer
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot. '/backup/util/xml/contenttransformer/xml_contenttransformer.class.php');

/**
 * Implementation of xml_contenttransformer to add CDATA tags
 *
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_xml_transformer extends xml_contenttransformer {

    /**
     * Modify the content before it is writter to a file
     *
     * @param string|mixed $content
     */
    public function process($content) {

        if (is_numeric($content) ||
            is_null($content) ||
            is_bool($content) ||
            $content == '') {
                return $content;
        }

        return '<![CDATA[' . $content . ']]>';
    }
}

