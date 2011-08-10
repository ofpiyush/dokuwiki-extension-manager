<?php
/**
 * Plugin management functions
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// todo
// - maintain a history of file modified
// - allow a plugin to contain extras to be copied to the current template (extra/tpl/)
// - to images (lib/images/) [ not needed, should go in lib/plugin/images/ ]

require_once(DOKU_PLUGIN."plugin/classes/ap_manage.class.php");
require_once(DOKU_PLUGIN."plugin/classes/ap_plugin.class.php");
require_once(DOKU_PLUGIN."plugin/classes/plugins_list.class.php");

//--------------------------[ GLOBALS ]------------------------------------------------
// note: probably should be dokuwiki wide globals, where they can be accessed by pluginutils.php
// global $plugin_types;
// $plugin_types = array('syntax', 'admin');

// plugins that are an integral part of dokuwiki, they shouldn't be disabled or deleted
global $plugin_protected;
$plugin_protected = array('acl','plugin','config','usermanager','revert');
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_plugin extends DokuWiki_Admin_Plugin {

    var $disabled = 0;
    var $plugin = NULL;
    var $cmd = 'plugin';
    var $handler = NULL;

    var $functions = array('delete','enable','update','disable',/*'settings',*/'info');  // require a plugin name
    var $commands = array('search','download','disdown'); // don't require a plugin name
    var $nav_tabs = array('plugin', 'template', 'search'); // navigation tabs
    var $plugin_list = array();
    var $template_list = array();

    var $msg = '';
    var $error = '';

    function __construct() {
        $this->disabled = plugin_isdisabled('plugin');
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 20;
    }

    /**
     * handle user request
     */
    function handle() {
        // enable direct access to language strings
        $this->setupLocale();
        $tab = (array_key_exists('tab',$_REQUEST) && in_array($_REQUEST['tab'],$this->nav_tabs))? $_REQUEST['tab'] : 'plugin';

        $this->cmd = $_REQUEST['fn'];
        if($this->cmd == 'multiselect' && is_array($_REQUEST['checked'])) {
            $this->cmd = $_REQUEST['action'];
            $this->plugin = $_REQUEST['checked'];
        }
        $this->_get_plugin_list();
        $this->_get_template_list();
        // verify $_REQUEST vars and check for security token
        if ($this->invalid_request()) {
            $this->cmd = 'plugin';
            $this->plugin = null;
        }
        
        if($this->cmd == 'plugin' && strlen($tab)) {
            $this->cmd = $tab;
        }
        // create object to handle the command
        $class = "ap_".$this->cmd;
        $path = DOKU_PLUGIN."/plugin/classes/$class.class.php";
        if(file_exists($path) && require_once(DOKU_PLUGIN."/plugin/classes/$class.class.php"))
            if(class_exists($class) && is_subclass_of($class,'ap_manage'))
                $this->handler = new $class($this);

        if(is_null($this->handler)) $this->handler = new ap_plugin($this);
        $this->msg = $this->handler->process();

    }
    function invalid_request() {
        //if command is empty, we need to make it
        if(empty($this->cmd)) return true;
        
        if(in_array($this->cmd, $this->commands)) return false;
        if(in_array($this->cmd, $this->functions)) {
            if(count(array_intersect($this->plugin, $this->plugin_list)) == count($this->plugin)) return false;
            if(count(array_intersect($this->plugin, $this->template_list)) == count($this->plugin)) return false;
        }
        if(checkSecurityToken()) return false;
        return true;
    }

    /**
     * output appropriate html
     */
    function html() {
        // enable direct access to language strings
        $this->setupLocale();
        $this->_get_plugin_list();

        if (is_null($this->handler)) $this->handler = new ap_plugin($this);

        ptln('<div id="plugin__manager">');
        $this->handler->html();
        ptln('</div><!-- #plugin_manager -->');
    }

    /**
     * Returns a list of all plugins, including the disabled ones
     */
    private function _get_plugin_list() {
        if (empty($this->plugin_list)) {
            $list = plugin_list('',true);     // all plugins, including disabled ones
            trigger_event('PLUGIN_PLUGINMANAGER_PLUGINLIST',$list);
            $this->plugin_list = $list;
        }
        return $this->plugin_list;
    }

    /**
     * Returns a list of all templates, including the disabled ones
     */
    private function _get_template_list() {
        if(empty($this->template_list)) {
            $tpl_dir = DOKU_INC.'lib/tpl/';
            $list = array();
            if($dh = @opendir($tpl_dir)) {
                while(false !== ($template = readdir($dh))) {
                    if($template[0] == '.') continue;
                    if(is_dir($tpl_dir.$template)) {
                        //FIXME No absolute check to determine if it is a template or any other directory
                        $list[] = $template;
                    }
                }
            }
            trigger_event('PLUGIN_PLUGINMANAGER_TEMPLATELIST',$list);
            $this->template_list = $list;
        }
        return $this->template_list;
    }

}
