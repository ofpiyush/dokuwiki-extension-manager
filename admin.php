<?php
/**
 * Plugin management functions
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
//ini_set('display_errors','on');
//error_reporting(E_STRICT);

// todo
// - maintain a history of file modified
// - allow a plugin to contain extras to be copied to the current template (extra/tpl/)
// - to images (lib/images/) [ not needed, should go in lib/plugin/images/ ]

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
    var $cmd = 'display';
    var $tab = 'plugin';
    var $log = null;
    var $repo = array();
    var $info = null;
    var $_bundled = array();
    var $handler = NULL;
    var $showinfo = null;
    

    var $functions = array('delete','enable','update','disable','reinstall',/*'settings',*/'info');  // require a plugin name
    var $commands = array('search','download','disdown'); // don't require a plugin name
    var $nav_tabs = array('plugin', 'template', 'search'); // navigation tabs
    var $plugin_list = array();
    var $template_list = array();

    var $msg = '';
    var $error = '';

    function __construct() {
        $this->disabled = plugin_isdisabled('plugin');
        spl_autoload_register(array($this,'autoload'));
        $this->_bundled = array('acl','plugin','config','info','usermanager','revert','popularity','safefnrecode','default');
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
        global $JSINFO;
        $JSINFO['pm_delconfirm_text'] = $this->getLang('confirm_del');
        $this->_get_plugin_list();
        $this->_get_template_list();
        if(isset($_REQUEST['info']))
            $this->showinfo = $_REQUEST['info'];
        // enable direct access to language strings if used anywhere
        $this->setupLocale();
        if(!empty($_REQUEST['tab']) && in_array($_REQUEST['tab'],$this->nav_tabs)) {
                $this->tab = $_REQUEST['tab'];
        } else {
            $this->tab = 'plugin';
        }
        $this->log = new pm_log_lib($this);
        $repo = new pm_repository_lib($this);
        $this->repo = $repo->get();
        $this->info = new pm_info_lib($this);
        $this->setup_action();
        $this->handler = $this->instantiate($this->tab,'tab');
        if(is_null($this->handler)) $this->handler = new pm_plugin_tab($this);
        $this->msg = $this->handler->process();
    }

    function setup_action() {
        $fn = $_REQUEST['fn'];
        if (is_array($fn)) {
            $this->cmd = key($fn);
        } else {
            $this->cmd = $fn;
        }
        //still here to allow reverting to multiselect
        if($this->cmd == 'multiselect') {
            $this->cmd = $_REQUEST['action'];
        }
        if(!empty($_REQUEST['checked'])) {
            $this->plugin = $_REQUEST['checked'];
        }
        // verify $_REQUEST vars and check for security token
        if ($this->valid_request()) {
            $this->action = $this->instantiate($this->cmd,'action');
        }
    }
    function instantiate($name,$type) {
        $class = 'pm_'.$name."_".$type;
        if(class_exists($class))
            return new $class($this);
        return null;
    }

    function valid_request() {
        //if command is empty, we need to make it
        if(empty($this->cmd)) return false;         
        if(in_array($this->cmd, $this->commands)) return true;
        if(in_array($this->cmd, $this->functions) && checkSecurityToken()) {
            if(count(array_intersect($this->plugin, $this->plugin_list)) == count($this->plugin)) return true;
            if(count(array_intersect($this->plugin, $this->template_list)) == count($this->plugin)) return true;
            if($this->cmd == 'info' && $this->tab == "search") return true;
        }
        return false;
    }

    /**
     * output appropriate html
     */
    function html() {
        // enable direct access to language strings
        $this->setupLocale();
        $this->_get_plugin_list();

        if (is_null($this->handler)) {
            $this->handler = new pm_plugin_tab($this);
            $this->handler->process();
        }

        ptln('<div id="plugin__manager">');
        $this->handler->html();
        ptln('</div><!-- #plugin_manager -->');
    }

    function autoload($class) {
        if(stripos($class,'pm_')===0) {
            $folder = @end(explode('_',$class));
            $path = DOKU_PLUGIN.'plugin/classes/'.$folder.'/'.$class.".class.php";
            if(@file_exists($path)) {
                require_once($path);
                return true;
            }
        }
        return;
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
