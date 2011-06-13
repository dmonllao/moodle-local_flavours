<?php 

/**
 * Abstract class to define the ingredients interface
 * 
 * @abstract
 * @package local
 * @subpackage flavours
 * @copyright 2011 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class flavours_ingredient {
    
    /**
     * The number of whitespaces to add before starting the xml tags output
     * @var integer
     */
    protected $prefix = 4;
    
    /**
     * Gets the an ingredients list with the ingredients availables on the system 
     */
    abstract public function get_system_data();

    
    /**
     * Stores the selected ingredients into the flavour folder
     * 
     * @param xml_writer $xmlwriter The XML writer, by reference
     * @param array $ingredients The ingredients to store
     * @param string $path Where to store the flavour tmp files
     */
    abstract public function package_ingredients(&$xmlwriter, $ingredients, $path);
}
