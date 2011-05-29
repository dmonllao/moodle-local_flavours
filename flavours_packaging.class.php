<?php 

require_once(dirname(__FILE__) . '/flavours.class.php');

class flavours_packaging extends flavours {
    
    protected function packaging_form() {
        
        global $CFG;
        
        $this->output = 'I\'ll get a form and display it, on submit you will be redirected to ';
        $this->output.= '<a href="'.$this->url.'?action=packaging_execute">the compressed file</a>';
    }
    
    protected function packaging_execute() {
        $this->output = 'I\'ll be a tasty compressed file';
    }
}
