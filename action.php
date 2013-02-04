<?php
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * AJAX handler for extension details query
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Håkan Sandell <sandell.hakan@gmail.com>
 */
class action_plugin_extension extends DokuWiki_Action_Plugin {

    /** @var helper_plugin_extension $hlp */
    public $hlp = null;

    /** @var pm_plugin_tab $handler */
    public $handler = null;

    /**
     * Constructor.
     *
     * Intitializes the helper class
     */
    function __construct() {
        $this->hlp =& plugin_load('helper', 'extension');
        if(!$this->hlp) msg('Loading the extension manager helper failed.', -1);
    }

    /**
     * register the eventhandlers
     */
    public function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call', array());
    }

    /**
     * Dispatch AJAX call to correct sub function
     *
     * @param Doku_Event $event
     * @param array      $params
     */
    public function handle_ajax_call(&$event, $params) {
        if($event->data != 'plugin_extension') return;
        $event->preventDefault();
        $event->stopPropagation();
        $this->hlp->init();

        if($_POST['fn']) {
            $this->extension_details();
        }
    }

    /**
     * Return rendered details about one extension
     * fn[] should look like '[info][repokey]'
     */
    protected function extension_details() {
        $fn = $_POST['fn'];
        preg_match('/(?<=\[info\]\[).+[^\]]/', $fn, $repokey);

        $info          = $this->hlp->info->get($repokey[0]);
        $this->handler = new pm_plugin_tab($this);
        $list          = new pm_plugins_list_lib($this, 'extensionplugin__pluginsinfo');
        echo $list->make_info($info);
    }

}
