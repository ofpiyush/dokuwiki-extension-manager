var plugin_manager = { 
    constructor : function () {
        jQuery('#plugin__manager .delete').click(function() {confirm("Are you sure?");});
    }
};
jQuery(plugin_manager.constructor);
