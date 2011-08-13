<?php
class action_plugin_plugin extends DokuWiki_Action_Plugin {
    function register($controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER',  $this, 'add_lang');
    }
    function add_lang($event, $param) {
        global $JSINFO;
        $JSINFO['pm_delconfirm_text'] = $this->getLang('confirm_del');
    }
}
