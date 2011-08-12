<?php 

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
