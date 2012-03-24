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
 * Moodle flavours renderable classes
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Renderable class to display the deployment results
 *
 * @package    local
 * @subpackage flavours
 * @copyright  2011 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flavours_renderable_deployment_execute implements renderable {

    protected $table;


    /**
     * Just a setter
     * @param html_table $table
     */
    public function __construct(html_table $table) {
        $this->table = $table;
    }

    /**
     * $table getter
     * @return html_table
     */
    public function get_table() {
        return $this->table;
    }

}


/**
 * Simple class to pass info to the generatedefaults_execute renderer
 */
class flavours_renderable_generatedefaults_execute implements renderable {
    
    protected $info;
    protected $phparray;
    
    /**
     * Sets the instance attributes
     * @param array $phparray
     * @param object $info
     */
    public function __construct($phparray, $info = false) {
        $this->phparray = $phparray;
        $this->info = $info;
    }
    
    /**
     * phparray getter
     */
    public function get_phparray() {
        return $this->phparray;
    }
    
    /**
     * info getter
     */
    public function get_info() {
        return $this->info;
    }
}
