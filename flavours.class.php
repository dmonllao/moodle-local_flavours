<?php 


/**
 * Wrapps the tool actions
 */
abstract class flavours {

    protected $action;
    protected $output;
    protected $url;
    protected $langfile;
    
    public function __construct($action) {
        
        global $CFG;
        
        $this->action = $action;
        $this->url = $CFG->wwwroot.'/local/flavours/index.php';
    }
    
    
	public function print_header() {
	    
	    global $PAGE, $OUTPUT, $SITE;
	    
	    $actualsettingspage = array_shift(explode('_', $this->action));
	    admin_externalpage_setup('local_flavours_'.$actualsettingspage);
	        
	    $PAGE->set_heading($SITE->fullname);
	    $PAGE->set_title(get_string('action'.$this->action, 'local_flavours'));
	        
	    return $OUTPUT->header();
	}

	
    public function process_wrapper() {
        $this->{$this->action}();
    }
	
    
	public function display() {
	    return $this->output;
	}
	
}
