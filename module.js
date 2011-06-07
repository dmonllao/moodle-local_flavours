
M.local_flavours = {

    tree: null,
    nodes: null,
    

    /**
     * Initializes the TreeView object and adds the submit listener
     */ 
    init: function(Y) {

        Y.use('yui2-treeview', function(Y) {
            
            var context = M.local_flavours;
            
            context.tree = new YAHOO.widget.TreeView("id_ingredients_tree");
    
            context.nodes = new Array();
            context.nodes['root'] = context.tree.getRoot();
        });
    },

    
    render: function(Y) {
        
        var context = M.local_flavours;

        //context.tree.expandAll();
        context.tree.setNodesProperty('propagateHighlightUp', true);
        context.tree.setNodesProperty('propagateHighlightDown', true);
        context.tree.subscribe('clickEvent', context.tree.onEventToggleHighlight);
        context.tree.render();
    
    
        // Listener to create one node for each selected setting
        YAHOO.util.Event.on('id_ingredients_submit', 'click', function() {
    
            // We need the moodle form to add the checked settings
            var FlavoursForm = document.getElementById('mform1');

            // Only the highlighted nodes
            var hiLit = context.tree.getNodesByProperty('highlightState', 1);
            if (YAHOO.lang.isNull(hiLit)) { 
                YAHOO.log("Nothing selected");
    
            } else {
    
                for (var i=0 ; i<hiLit.length ; i++) {
    
                    treeNode = hiLit[i];

                    // The way to identify a ingredient (ingredients branches not allowed)
                    if (treeNode.target != 'undefined' && treeNode.target != '') {
    
                        // If the node does not exists we add it
                        if (!document.getElementById(treeNode.target)) {
    
                            var ingredientelement = document.createElement('input');
                            ingredientelement.setAttribute('type', 'hidden');
                            ingredientelement.setAttribute('name', treeNode.target);
                            ingredientelement.setAttribute('value', '1');
                            FlavoursForm.appendChild(ingredientelement);
                        }
                    }
                }
            }
        });
    
    }

}
