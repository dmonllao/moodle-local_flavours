<?php 


/**
 * Wrapps the tool actions
 */
abstract class flavours {

    protected $action;
    protected $url;
    
    protected $output;
    protected $form;
    
    public function __construct($action) {
        
        global $CFG;
        
        $this->action = $action;
        $this->url = $CFG->wwwroot.'/local/flavours/index.php';
    }
    

    /**
     * Creates the tree structure based on $this->ingredients
     * 
     * @return array The tree html following the TreeView structure
     */
    protected function get_tree_ingredients() {
        
        global $PAGE;

        $treedata = '<ul>';
        foreach ($this->ingredients as $ingredienttype => $branch) {

            // A main ingredient/ namespace to ease the ingredients detection
            $prefix = 'ingredient/'.$ingredienttype;
            $this->get_tree_data($treedata, $branch, $prefix);
        }
        $treedata .= '</ul>';
        
        return $treedata;
    }
    
    
    /**
     * Gets the html to display the tree
     * 
     * @param string $output
     * @param object $branch
     * @param string $prefix
     */
    protected function get_tree_data(&$output, $branch, $prefix) {
        
        $output .= '<li>';
        $output .= $branch->name;
        
        $output .= '<ul>';
        foreach ($branch->branches as $name => $data) {
            
            // To identify that branch/leaf and pass it through his branches
            $branchprefix = $prefix.'/'.$data->id;
            
            // If it does not have children it's a leaf
            if (empty($data->branches)) {
                $output .= '<li><a target="'.$branchprefix.'">'.$data->name.'</a></li>';
                
            // Let's get the branch children
            } else {
                $this->get_tree_data($output, $data, $branchprefix);
            } 
        }
        
        $output .= '</ul>';
        $output .= '</li>';
    }
    
    
    /**
     * Sets the page info and returns the header to output
     */
    public function print_header() {
        
        global $PAGE, $OUTPUT, $SITE;
        
        $actualsettingspage = array_shift(explode('_', $this->action));
        admin_externalpage_setup('local_flavours_'.$actualsettingspage);
            
        $PAGE->set_heading($SITE->fullname);
        $PAGE->set_title(get_string('action'.$this->action, 'local_flavours'));
            
        return $OUTPUT->header();
    }

    
    /**
     * Centralized output
     */
    public function display() {
        
        global $OUTPUT;
        
        echo $this->print_header();
        
        if (!empty($this->form)) {
            $this->form->display();
        }
        echo $this->output;
        echo $OUTPUT->footer();
    }
    
}
