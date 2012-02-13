<?php
/**
 * Extension management functions
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_TPLLIB')) define('DOKU_TPLLIB',DOKU_INC.'lib/tpl/');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_extension extends DokuWiki_Admin_Plugin {

    /**
     * Array of extensions sent by POST method
     */
    var $selection = NULL;

    /**
     * The action to be carried out
     * one from either admin_plugin_extension::$functions or admin_plugin_extension::$commands
     */
    var $cmd = 'display';

    /**
     * The current tab which is being shown
     * one from admin_plugin_extension::$nav_tabs
     */
    var $tab = 'plugin';

    /**
     * Instance of pm_log_lib library (contains read-write functions for manager.dat)
     */
    var $log = null;

    /**
     * Copy of the repository array
     */
    var $repo = array();

    /**
     * Instance of the pm_info_lib library (creates single info objects)
     */
    var $info = null;

    /**
     * array list of bundled plugins
     */
    var $_bundled = array('acl','plugin','config','info','usermanager','revert','popularity','safefnrecode','template:default');

    /**
     * plugins that are an integral part of dokuwiki, this is only valid for pre-"Angua" releases
     * now this information is stored in 'conf/plugins.required.php'
     */
    var $legacy_protected = array('acl','plugin','config','usermanager');

    /**
     * plugins that are protected from being managed with the extension manager
     */
    var $protected = array();

    /**
     * Instance of the tab from admin_plugin_extension::$tab
     */
    var $handler = NULL;

    /**
     * If a plugin has info clicked, its "id"
     * @see pm_base_single_lib::$id
     */
    var $showinfo = null;

    /**
     * list of valid actions(classes/action/*.class.php)
     */
    var $valid_actions = array('delete','enable','update','disable','reinstall','info','search','download','download_disabled','repo_reload');

    /**
     * array of navigation tab ids
     */
    var $nav_tabs = array('plugin', 'template', 'search');

    /**
     * array list of installed plugin foldernames
     * saved after the trigger 'PLUGIN_PLUGINMANAGER_PLUGINLIST'
     */
    var $plugin_list = array();

    /**
     * bool indicating whether directory DOKU_PLUGIN is writable or not
     */
    var $pluginfolder_writable = false;

    /**
     * array list of installed template foldernames
     * saved after the trigger 'PLUGIN_PLUGINMANAGER_TEMPLATELIST'
     */
    var $template_list = array();

    /**
     * bool indicating whether directory DOKU_TPLLIB is writable or not
     */
    var $templatefolder_writable = false;

    /**
     * string current DokuWiki version
     */
    var $dokuwiki_version = null;

    var $dokuwiki = array('2012-01-25' => 'Angua',
                          '2011-05-25' => 'Rincewind',
                          '2010-11-07' => 'Anteater',
                          '2009-12-25' => 'Lemming');

    function __construct() {
        spl_autoload_register(array($this,'autoload'));

        if (function_exists('plugin_getcascade')) {
            $cascade = plugin_getcascade();
            if(!empty($cascade['protected'])) {
                $this->protected = array_keys($cascade['protected']);
            }
        } else {
            // support for using extension manager with pre-"Angua" (okt2011) releases
            $this->protected = $this->legacy_protected;
        }
        $this->pluginfolder_writable = is_writable(DOKU_PLUGIN);
        $this->templatefolder_writable = is_writable(DOKU_TPLLIB);

        $ver = getVersionData();
        if (preg_match('/\d+[-]\d+[-]\d+/',$ver['date'],$date)) {
            reset($this->dokuwiki);
            if ($ver['type'] == 'Git' && $date[0] > key($this->dokuwiki)) {
                $date[0] = key($this->dokuwiki);
            }
            $name = $this->dokuwiki[$date[0]];
            if (!$name) $name = $date[0];
            $this->dokuwiki_version = array('date' => $date[0], 'name' => $name);
        }
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
        $this->_get_plugin_list();
        $this->_get_template_list();
        if(isset($_REQUEST['info']))
            $this->showinfo = $_REQUEST['info'];
        //Setup the selected tab
        if(!empty($_REQUEST['tab']) && in_array($_REQUEST['tab'],$this->nav_tabs)) {
            $this->tab = $_REQUEST['tab'];
        } else {
            $this->tab = 'plugin';
        }
        $this->log = new pm_log_lib($this);
        $repo = new pm_repository_lib($this);
        $this->repo = $repo->get();
        $this->info = new pm_info_lib($this);
        //setup and carry out the action requested
        $this->setup_action();
        $this->handler = $this->instantiate($this->tab,'tab');
        if(is_null($this->handler)) $this->handler = new pm_plugin_tab($this);
        $this->handler->process();
    }

    /**
     * Determines which action has been requested and executes the action
     * stores name of action in admin_plugin_extension::$cmd and the 
     * instance of action in admin_plugin_extension::$action 
     */
    function setup_action() {
        $fn = $_REQUEST['fn'];
        if (is_array($fn)) {
            $this->cmd = key($fn);
            $extension = current($fn);
            if (is_array($extension)) {
                $this->selection = array_keys($extension);
            }
        } else {
            $this->cmd = $fn;
        }
        if(!empty($_REQUEST['checked'])) {
            $this->selection = $_REQUEST['checked'];
        }
        // verify $_REQUEST vars and check for security token
        if ($this->valid_request()) {
            $this->action = $this->instantiate($this->cmd,'action');
        }
    }

    /**
     * @param string name of the class to be instantiated
     * @param string type (classes/<foldername>) of the class
     * @return mixed object/null
     */
    function instantiate($name,$type) {
        $class = 'pm_'.$name."_".$type;
        if(class_exists($class))
            return new $class($this);
        return null;
    }

    /**
     * validate the request
     * @return bool if the requested action should be carried out or not
     */
    function valid_request() {
        //if command is empty, we need to make it
        if(empty($this->cmd)) return false;
        if(in_array($this->cmd, $this->valid_actions) && checkSecurityToken()) return true;
        return false;
    }

    /**
     * output appropriate html
     */
    function html() {

        if (is_null($this->handler)) {
            $this->_get_plugin_list();
            $this->handler = new pm_plugin_tab($this);
            $this->handler->process();
        }

        ptln('<div id="extension__manager">');
        print $this->locale_xhtml('extension_intro');
        ptln('<div class="panel">');
        $this->handler->html();
        ptln('</div><!-- panel -->');
        ptln('</div><!-- #extension__manager -->');
    }

    function getTOC() {
        if ($this->tab != 'plugin') return array();

        $toc = array();
        $toc[] = html_mktocitem('extension_manager', $this->getLang('menu'), 1);
        $toc[] = html_mktocitem('installed_plugins', $this->getLang('header_plugin_installed'), 2);
        $toc[] = html_mktocitem('protected_plugins', $this->getLang('header_plugin_protected'), 2);
        return $toc;
    }

    /**
     * Autoloader for the plugin manager
     * @param string classname to load
     */
    function autoload($class) {
        if(stripos($class,'pm_')===0) {
            $folder = @end(explode('_',$class));
            $path = DOKU_PLUGIN.'extension/classes/'.$folder.'/'.$class.".class.php";
            if(@file_exists($path)) {
                require_once($path);
                return true;
            }
        }
        return;
    }

    /**
     * Get plugin list
     * @return array list of plugins, including disabled ones
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
     * Get template list
     * @return array list of templates, including disabled ones
     */
    private function _get_template_list() {
        if(empty($this->template_list)) {
            $tpl_dir = DOKU_TPLLIB;
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

    /**
     * return a list (name & type) of all the component plugins that make up this plugin
     *
     */
    function get_plugin_components($plugin) {
        global $plugin_types;
        static $plugins;
        if(empty($plugins[$plugin])) {
            $components = array();
            $path = DOKU_PLUGIN.plugin_directory($plugin).'/';

            foreach ($plugin_types as $type) {
                if (@file_exists($path.$type.'.php')) { $components[] = array('name'=>$plugin, 'type'=>$type); continue; }

                if ($dh = @opendir($path.$type.'/')) {
                    while (false !== ($cp = readdir($dh))) {
                        if ($cp == '.' || $cp == '..' || strtolower(substr($cp,-4)) != '.php') continue;

                        $components[] = array('name'=>$plugin.'_'.substr($cp, 0, -4), 'type'=>$type);
                    }
                    closedir($dh);
                }
            }
            $plugins[$plugin] = $components;
        }
        return $plugins[$plugin];
    }
}
