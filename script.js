var extension_manager = { 

    init : function () {
        if (!('info' in extension_manager.getUrlVars())) {
            jQuery('#extensionplugin__searchtext').focus();
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

var extension_manager_qsearch = {

    $inObj: null,
    $outObj: null,
    timer: null,

    /**
     * initialize the quick search
     *
     * Attaches the event handlers
     *
     * @param input element (jQuery selector/DOM obj)
     * @param output element (jQuery selector/DOM obj)
     */
    init: function (input, output) {
        var do_qsearch;

        extension_manager_qsearch.$inObj  = jQuery(input);
        extension_manager_qsearch.$outObj = jQuery(output);

        // objects found?
        if (extension_manager_qsearch.$inObj.length === 0 ||
            extension_manager_qsearch.$outObj.length === 0) {
            return;
        }

        // attach eventhandler to search field
        do_qsearch = function () {
            extension_manager_qsearch.clear_results();
            var value = extension_manager_qsearch.$inObj.val();
            var type = extension_manager_qsearch.$inObj.parents().find('[name="type"]').attr('value');
            if (value === '') {
                return;
            }
            jQuery.post(
                DOKU_BASE + 'lib/exe/ajax.php',
                {
                    call: 'plugin_extension',
                    type: type,
                    q: encodeURI(value)
                },
                extension_manager_qsearch.onCompletion,
                'html'
            );
        };

        extension_manager_qsearch.$inObj.keyup(
            function() {
                if(extension_manager_qsearch.timer){
                    window.clearTimeout(extension_manager_qsearch.timer);
                    extension_manager_qsearch.timer = null;
                }
                extension_manager_qsearch.clear_results();
                extension_manager_qsearch.timer = window.setTimeout(do_qsearch, 500);
            }
        );

        // attach eventhandler to output field
        extension_manager_qsearch.$outObj.click(extension_manager_qsearch.clear_results);
    },

    /**
     * Empty and hide the output div
     */
    clear_results: function(){
        extension_manager_qsearch.$outObj.hide();
        extension_manager_qsearch.$outObj.text('');
    },

    /**
     * Callback. Reformat and display the results.
     *
     * Namespaces are shortened here to keep the results from overflowing
     * or wrapping
     *
     * @param data The result HTML
     */
    onCompletion: function(data) {
        var max, $links, too_big;

        if (data === '') { return; }

        extension_manager_qsearch.$outObj
            .html(data)
            .show()
            .css('white-space', 'nowrap');

        // shorten namespaces if too long
        max = extension_manager_qsearch.$outObj[0].clientWidth;
        $links = extension_manager_qsearch.$outObj.find('a');
        too_big = (document.documentElement.dir === 'rtl')
                  ? function (l) { return l.offsetLeft < 0; }
                  : function (l) { return l.offsetWidth + l.offsetLeft > max; };

        $links.each(function () {
            var start, length, replace, nsL, nsR, eli, runaway;

            if (!too_big(this)) {
                return;
            }

            nsL = this.innerText.indexOf('(');
            nsR = this.innerText.indexOf(')');
            eli = 0;
            runaway = 0;

            while((nsR - nsL > 3) && too_big(this) && runaway++ < 500) {
                if(eli !== 0){
                    // elipsis already inserted
                    if( (eli - nsL) > (nsR - eli) ){
                        // cut left
                        start = eli - 2;
                        length = 2;
                    }else{
                        // cut right
                        start = eli + 1;
                        length = 1;
                    }
                    replace = '';
                }else{
                    // replace middle with ellipsis
                    start = Math.floor( nsL + ((nsR-nsL)/2) );
                    length = 1;
                    replace = '�';
                }
                this.innerText = substr_replace(this.innerText,
                                                replace, start, length);

                eli = this.innerText.indexOf('�');
                nsL = this.innerText.indexOf('(');
                nsR = this.innerText.indexOf(')');
            }
        });
    }
};

jQuery(function () {
    extension_manager_qsearch.init('#extensionplugin__searchtext','#extensionplugin__searchresult');
});
