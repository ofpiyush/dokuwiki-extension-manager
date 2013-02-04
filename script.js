var extension_manager = {

    init : function () {
        if (!('info' in extension_manager.getUrlVars())) {
            jQuery('div.search #extensionplugin__searchtext').focus();
        }

        extension_manager.initInfoPanels();

        extension_manager.confirmDelete('#extension__manager .actions .delete');
    },


    /**
     * Adds open/close functionality for additional info
     */
    initInfoPanels: function(){
        jQuery('#extension__manager input.info').click(function (e) {
            var $clicky = jQuery(this);
            if($clicky.hasClass('close')){
                $clicky.parent().find('dl.details').remove();
                $clicky.removeClass('close');
            }else{
                jQuery.post(
                    DOKU_BASE + 'lib/exe/ajax.php',
                    {
                        call: 'plugin_extension',
                        fn: $clicky.attr('name')
                    },
                    function(data) {
                        if (data === '') return;
                        jQuery(data).show().insertAfter($clicky);
                    },
                    'html'
                );
                $clicky.addClass('close');
            }
            e.preventDefault();
            e.stopPropagation();
        });
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

    confirmDelete : function (delSelector) {
        jQuery(delSelector).click(function(e) {
            if(!confirm(LANG.plugins['extension']['confirm_del'])) { e.preventDefault(); }});
    }
};
jQuery(extension_manager.init);
