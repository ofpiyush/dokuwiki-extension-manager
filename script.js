var plugin_manager = { 
    constructor : function () {
        jQuery('#plugin__manager .actions .delete , #plugin__manager .bottom .button[name="fn[delete]"]').click(function(e) {
            if(!confirm("Are you sure?")) { e.preventDefault(); }});
    }
};
jQuery(plugin_manager.constructor);
