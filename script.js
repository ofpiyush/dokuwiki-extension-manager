var extension_manager = { 
    constructor : function () {
        jQuery('#extensionplugin__searchtext').focus();
        extension_manager.setCheckState('#extension__manager .checknone',false);
	    extension_manager.setCheckState('#extension__manager .checkall',true);
	    extension_manager.confirmDelete('#extension__manager .actions .delete');
        extension_manager.confirmDelete('#extension__manager .bottom .button[name="fn[delete]"]');
    },
    setCheckState : function (clickSelector,bool) {
        jQuery(clickSelector).show();
        jQuery(clickSelector).click(function () {
		        jQuery(this).parents('form').find('input[type="checkbox"]').not('[disabled]').prop('checked',bool);
	        });
    },
    confirmDelete : function (delSelector) {
        jQuery(delSelector).click(function(e) {
            if(!confirm(LANG.plugins['extension']['confirm_del'])) { e.preventDefault(); }});
    }
};
jQuery(extension_manager.constructor);

