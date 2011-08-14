<?php 

require_once($CFG->dirroot . '/backup/util/xml/contenttransformer/xml_contenttransformer.class.php');

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

