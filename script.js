var plugin_manager = { 
    constructor : function () {
        jQuery('#plugin__manager .delete').click(function(e) {if(!confirm("Are you sure?")) { e.preventDefault(); }});
    }
};
jQuery(plugin_manager.constructor);
