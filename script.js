var plugin_manager = { 
    constructor : function () {
        plugin_manager.setCheckState('#plugin__manager .checknone',false);
	    plugin_manager.setCheckState('#plugin__manager .checkall',true);
	    plugin_manager.confirmDelete('#plugin__manager .actions .delete');
        plugin_manager.confirmDelete('#plugin__manager .bottom .button[name="fn[delete]"]');
    },
    setCheckState : function (clickSelector,bool) {
        jQuery(clickSelector).show();
        jQuery(clickSelector).click(function () {
		        jQuery(this).parents('form').find('input[type="checkbox"]').not('[disabled]').prop('checked',bool);
	        });
    },
    confirmDelete : function (delSelector) {
        jQuery(delSelector).click(function(e) {
            if(!confirm(JSINFO['pm_delconfirm_text'])) { e.preventDefault(); }});
    }
};
jQuery(plugin_manager.constructor);
