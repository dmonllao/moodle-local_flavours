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
     * Gets the ingredients availables on the system 
     */
    public function get_system_data() {}
    
}
