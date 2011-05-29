<?php 

require_once(dirname(__FILE__) . '/flavours.class.php');

class flavours_deployment extends flavours {
    

    public function deployment_upload() {
        $this->output = 'I\'ll be a file picker and I\'ll redirect the user to ';
        $this->output.= '<a href="'.$this->url.'?action=deployment_preview">the previsualization flavour page</a>';
    }
    
    public function deployment_preview() {
        $this->output = 'I\'ll be the list of the flavour contents ';
        $this->output.= '(see http://docs.moodle.org/en/Development:Moodle_flavours#Mockups for more info) ';
        $this->output.= 'and the next step is the deployment itself and ';
        $this->output.= '<a href="'.$this->url.'?action=deployment_execute">the results page</a>';
    }
    
    public function deployment_execute() {
        $this->output = 'That\'s all for now';
    }
}