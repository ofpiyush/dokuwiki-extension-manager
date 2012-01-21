var extension_manager = { 
    constructor : function () {
        if (!('info' in extension_manager.getUrlVars())) {
            jQuery('#extensionplugin__searchtext').focus();
        }
        extension_manager.setCheckState('#extension__manager .checknone',false);
	    extension_manager.setCheckState('#extension__manager .checkall',true);
	    extension_manager.confirmDelete('#extension__manager .actions .delete');
        extension_manager.confirmDelete('#extension__manager .bottom .button[name="fn[delete]"]');
    },
    getUrlVars : function() {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for(var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
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

