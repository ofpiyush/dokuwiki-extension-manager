var extension_manager = {

    init : function () {
        if (!('info' in extension_manager.getUrlVars())) {
            jQuery('div.search #extensionplugin__searchtext').focus();
        }

        extension_manager.initInfoPanels();

        // check all/none buttons
        jQuery('#extension__manager .checks').show();
        extension_manager.setCheckState('#extension__manager .checknone',false);
        extension_manager.setCheckState('#extension__manager .checkall',true);
        extension_manager.confirmDelete('#extension__manager .actions .delete');
        extension_manager.confirmDelete('#extension__manager .bottom .button[name="fn[delete]"]');
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
jQuery(extension_manager.init);
