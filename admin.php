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

/**
 * The extension manager admin interface.
 *
 * Most functionality is provided by special manager classes that take this instance and
 * work on its public members
 */
class admin_plugin_extension extends DokuWiki_Admin_Plugin {
    /** @var helper_plugin_extension */
    public $hlp = null;

    /** @var array $selection extensions sent by POST method */
    public $selection = null;

    /**
     * The action to be carried out
     * one from either admin_plugin_extension::$functions or admin_plugin_extension::$commands
     */
    public $cmd = 'display';

    /**
     * The current tab which is being shown
     * one from admin_plugin_extension::$nav_tabs
     *
     * @var string $tab
     */
    public $tab = 'plugin';

    /**
     * Instance of the tab from admin_plugin_extension::$tab
     * @var pm_base_tab $handler
     */
    public $handler = null;

    /**
     * If a plugin has info clicked, its "id"
     *
     * @see pm_base_single_lib::$id
     * @var string $showinfo
     */
    public $showinfo = null;

    /**
     * list of valid actions(classes/action/*.class.php)
     */
    public $valid_actions = array('delete', 'enable', 'update', 'disable', 'disable_all', 'reinstall', 'info', 'search', 'download', 'repo_reload');

    /**
     * array of navigation tab ids
     */
    public $nav_tabs = array('plugin', 'template', 'search');

    /**
     * Constructor. Initializes the helper plugin
     */
    public function __construct() {
        $this->hlp =& plugin_load('helper', 'extension');
        if(!$this->hlp) msg('Loading the extension manager helper failed.', -1);
    }

    /**
     * return sort order for position in admin menu
     */
    public function getMenuSort() {
        return 20;
    }

    /**
     * handle user request
     */
    public function handle() {
        $this->hlp->init();

        if(isset($_REQUEST['info']))
            $this->showinfo = $_REQUEST['info'];
        //Setup the selected tab
        if(!empty($_REQUEST['tab']) && in_array($_REQUEST['tab'], $this->nav_tabs)) {
            $this->tab = $_REQUEST['tab'];
        } else {
            $this->tab = 'plugin';
        }
        //setup and carry out the action requested
        $this->setup_action();
        $this->handler = $this->instantiate($this->tab, 'tab');
        if(is_null($this->handler)) $this->handler = new pm_plugin_tab($this);
        $this->handler->process();
    }

    /**
     * Determines which action has been requested and executes the action
     * stores name of action in admin_plugin_extension::$cmd and the
     * instance of action in admin_plugin_extension::$action
     */
    protected function setup_action() {
        $fn = $_REQUEST['fn'];
        if(is_array($fn)) {
            $this->cmd = key($fn);
            $extension = current($fn);
            if(is_array($extension)) {
                $this->selection = array_keys($extension);
            }
        } else {
            $this->cmd = $fn;
        }
        // verify $_REQUEST publics and check for security token
        if($this->valid_request()) {
            $this->instantiate($this->cmd, 'action');
        }
    }

    /**
     * Initializes one of the manager classes
     *
     * @todo this should be superseeded by the autoloader
     * @param string $name name of the class to be instantiated
     * @param string $type (classes/<foldername>) of the class
     * @return mixed object/null
     */
    public function instantiate($name, $type) {
        $class = 'pm_'.$name."_".$type;
        if(class_exists($class))
            return new $class($this);
        return null;
    }

    /**
     * validate the request
     *
     * @return bool if the requested action should be carried out or not
     */
    public function valid_request() {
        //if command is empty, we need to make it
        if(empty($this->cmd)) return false;
        if(in_array($this->cmd, $this->valid_actions) && checkSecurityToken()) return true;
        return false;
    }

    /**
     * output appropriate html
     */
    public function html() {
        ptln('<div id="extension__manager">');
        print $this->locale_xhtml('extension_intro');
        ptln('<div class="panel">');
        $this->handler->html();
        ptln('</div><!-- panel -->');
        ptln('</div><!-- #extension__manager -->');
    }

    /**
     * Create the table of contents
     *
     * @return array
     */
    public function getTOC() {
        if($this->tab != 'plugin') return array();

        $toc   = array();
        $toc[] = html_mktocitem('extension_manager', $this->getLang('menu'), 1);
        $toc[] = html_mktocitem('installed_plugins', $this->getLang('header_plugin_installed'), 2);
        $toc[] = html_mktocitem('protected_plugins', $this->getLang('header_plugin_protected'), 2);
        return $toc;
    }

}
