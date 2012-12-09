<?php
/**
 * Extension manager helper, makes repo, log etc available
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Hakan Sandell <hakan.sandell@home.se>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_TPLLIB')) define('DOKU_TPLLIB', DOKU_INC.'lib/tpl/');

class helper_plugin_extension extends DokuWiki_Plugin {

    /**
     * Instance of plugin class using the helper, to access getLang/getConf
     */
    var $manager = null;

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
    var $_bundled = array('acl', 'plugin', 'config', 'info', 'usermanager', 'revert', 'popularity', 'safefnrecode', 'template:default');

    /**
     * plugins that are an integral part of dokuwiki, this is only valid for pre-"Angua" releases
     * now this information is stored in 'conf/plugins.required.php'
     */
    var $legacy_protected = array('acl', 'plugin', 'config', 'usermanager');

    /**
     * plugins that are protected from being managed with the extension manager
     */
    var $protected = array();

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

    var $dokuwiki = array(
        '2012-01-25' => 'Angua',
        '2011-05-25' => 'Rincewind',
        '2010-11-07' => 'Anteater',
        '2009-12-25' => 'Lemming'
    );

    function __construct() {
        spl_autoload_register(array($this, 'autoload'));
    }

    function init($manager) {
        $this->manager = $manager;

        if(function_exists('plugin_getcascade')) {
            $cascade = plugin_getcascade();
            if(!empty($cascade['protected'])) {
                $this->protected = array_keys($cascade['protected']);
            }
        } else {
            // support for using extension manager with pre-"Angua" (okt2011) releases
            $this->protected = $this->legacy_protected;
        }
        $this->pluginfolder_writable   = is_writable(DOKU_PLUGIN);
        $this->templatefolder_writable = is_writable(DOKU_TPLLIB);

        $ver = getVersionData();
        if(preg_match('/\d+[-]\d+[-]\d+/', $ver['date'], $date)) {
            reset($this->dokuwiki);
            if($ver['type'] == 'Git' && $date[0] > key($this->dokuwiki)) {
                $date[0] = key($this->dokuwiki);
            }
            $name = $this->dokuwiki[$date[0]];
            if(!$name) $name = $date[0];
            $this->dokuwiki_version = array('date' => $date[0], 'name' => $name);
        }

        $this->get_plugin_list();
        $this->get_template_list();

        $this->log  = new pm_log_lib($this);
        $repo       = new pm_repository_lib($this);
        $this->repo = $repo->get();
        $this->info = new pm_info_lib($this);
    }

    /**
     * Autoloader for the plugin manager
     *
     * @param string classname to load
     */
    function autoload($class) {
        if(stripos($class, 'pm_') === 0) {
            $folder = @end(explode('_', $class));
            $path   = DOKU_PLUGIN.'extension/classes/'.$folder.'/'.$class.".class.php";
            if(@file_exists($path)) {
                require_once($path);
                return true;
            }
        }
        return;
    }

    /**
     * Get plugin list
     *
     * @return array list of plugins, including disabled ones
     */
    private function get_plugin_list() {
        if(empty($this->plugin_list)) {
            $list = plugin_list('', true); // all plugins, including disabled ones
            trigger_event('PLUGIN_PLUGINMANAGER_PLUGINLIST', $list);
            $this->plugin_list = $list;
        }
        return $this->plugin_list;
    }

    /**
     * Get template list
     *
     * @return array list of templates, including disabled ones
     */
    private function get_template_list() {
        if(empty($this->template_list)) {
            $tpl_dir = DOKU_TPLLIB;
            $list    = array();
            if($dh = @opendir($tpl_dir)) {
                while(false !== ($template = readdir($dh))) {
                    if($template[0] == '.') continue;
                    if(is_dir($tpl_dir.$template)) {
                        //FIXME No absolute check to determine if it is a template or any other directory
                        $list[] = $template;
                    }
                }
            }
            trigger_event('PLUGIN_PLUGINMANAGER_TEMPLATELIST', $list);
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
            $path       = DOKU_PLUGIN.plugin_directory($plugin).'/';

            foreach($plugin_types as $type) {
                if(@file_exists($path.$type.'.php')) {
                    $components[] = array('name' => $plugin, 'type' => $type);
                    continue;
                }

                if($dh = @opendir($path.$type.'/')) {
                    while(false !== ($cp = readdir($dh))) {
                        if($cp == '.' || $cp == '..' || strtolower(substr($cp, -4)) != '.php') continue;

                        $components[] = array('name' => $plugin.'_'.substr($cp, 0, -4), 'type' => $type);
                    }
                    closedir($dh);
                }
            }
            $plugins[$plugin] = $components;
        }
        return $plugins[$plugin];
    }

    /**
     * Filter BEFORE the repo is searched on, removes obsolete plugins, security issues etc
     */
    function get_filtered_repo() {
        $retval = array();
        if($this->repo) {
            $retval = array_filter($this->repo['data'], create_function('$info', 'return $info["show"];'));
            $retval = array_merge($retval, $this->local_extensions());
        } else {
            $retval = $this->local_extensions();
        }
        uasort(
            $retval, function ($a, $b) {
                return strcasecmp($a['sort'], $b['sort']);
            }
        );
        return $retval;
    }

    /**
     * Create dummy repo entries for local extensions
     */
    function local_extensions() {
        $retval    = array();
        $templates = array_map(array($this, '_info_templatelist'), $this->template_list);
        $plugins   = array_map(array($this, '_info_pluginlist'), $this->plugin_list);
        $list      = array_merge($plugins, $templates);
        foreach($list as $info) {
            if($info->repo) {
                // only use repo if we are sure that this plugin is connected to repo
                $retval[$info->repokey]       = $info->repo;
                $retval[$info->repokey]['id'] = $info->cmdkey;
            } else {
                $retval['L'.$info->repokey] = array(
                    'id'          => $info->cmdkey,
                    'name'        => $info->name,
                    'author'      => $info->author,
                    'description' => $info->desc,
                    'sort'        => str_replace('template:', '', $info->repokey)
                );
            }
        }
        return $retval;
    }

    function _info_pluginlist($index) {
        return $this->info->get($index, 'plugin');
    }

    function _info_templatelist($index) {
        return $this->info->get($index, 'template');
    }

    function make_extensionsearchlink($id) {
        global $ID;

        $params = array(
            'do'   => 'admin',
            'page' => 'extension',
            'tab'  => 'search',
            'q'    => 'id:'.$id,
        );
        $url    = wl($ID, $params);
        return '<a href="'.$url.'" class="searchlink" title="'.hsc($id).'">'.hsc(ucfirst($id)).'</a>';
    }

}

