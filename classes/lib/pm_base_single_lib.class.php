<?php
/**
 * Detailed info object for a single extension (plugin/template)
 * it also defines capabilities like 'can_enable'
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

/**
 * For installed plugins only (can be used while downloading too)
 * @property string $name Name of the plugin/template defaults to "id"
 * @property string $update_available if new version is available
 * @property string $url for more info
 * @property string $screenshoturl Screenshoturl (from repo) Only applicable if $is_template = true;
 * @property string $securityissue from repo
 * @property string $securitywarning fromrepo
 * @property array $depends Dependencies
 * @property array $similar Similar plugins
 * @property array $conflicts conflicting plugins
 * @property string $author Author of the plugin/ template
 * @property string $email Email if of the author
 * @property string $installed RFC 2822 date Time of install if done via the plugin manager
 * @property string $installed RFC 2822 date time of update
 * @property string $downloadurl URL for download
 * @property string $base "base" from *.info.txt defaults to "id"
 */


abstract class pm_base_single_lib {
    /**
     * Identifier, name of directory for installed plugins/templates OR "id" without any prefix for repository entries
     * There might be clashes between plugin & template id's. The directory might also not be the same as "base" in info.txt
     * @property string $id
     */
    var $id = null;

    /**
     * Unique identifier, $id prefixed with 'template:' for templates
     * @property string $id
     */
    var $repokey = null;

    /**
     * If the manager can write in the folder
     * @var bool
     */ 
    var $is_writable = false;

    /**
     * If the extension is version controlled by git
     * @var bool
     */
    var $is_gitmanaged = false;

    /**
     * if it is a plugin or a template
     * @var bool
     */
    var $is_template = false;

    /**
     * If plugin is bundled with currently installed version of DokuWiki
     * @var bool
     */

     var $is_bundled = false;

     /**
     * If plugin is protected (shouldn't be managed)
     * @var bool
     */
    var $is_protected = false;

    /**
     * If plugin is enabled
     * @var bool
     */
    var $is_enabled = false;

    /**
     * Content in $repo[] array (see http://www.dokuwiki.org/plugin:repository:manual)
     */
    var $repo = array();

    /**
     * Content in $info[] array (http://www.dokuwiki.org/devel:plugin_info)
     * @property string base    The technical name of the plugin. Plugin Manager will install it into this directory.
     * @property string author  The full name of the plugin author
     * @property string email   E-Mail to contact the plugin author about this plugin
     * @property string date    The date of the last update of this plugin in YYYY-MM-DD form. Don't forget to update this when you update your plugin!
     * @property string name    The human readable name of the plugin
     * @property string desc    A description of what the plugin does
     * @property string url     URL to where more info about the plugin is available
     */
    var $info = array();

    /**
     * Content in $log[] array
     * @property string downloadurl     URL used for last download
     * @property string installed       RFC 2822 date time of installation
     * @property string updated         RFC 2822 date time of update
     */
    var $log = array();

    function __construct(admin_plugin_extension $manager,$id,$is_template) {
        $this->manager = $manager;
        $this->id = $id;
        $this->is_template = $is_template;

        if($is_template) {
            $this->is_bundled = ($id == 'default'); // TODO include in bundled array?
            // no protected templates
        } else {
            $this->is_bundled = in_array($id,$manager->_bundled);
            $this->is_protected = in_array($id,$manager->protected);
        }
    }

    /**
     * Precedence order for accessing properties
     *      get(method) -> *.info.txt -> repository -> $log -> default(method)
     */
    function __get($key) {
        $return = false;

        if(method_exists($this,'get_'.$key)) { 
            // do not cache anything returned from a method
            // if its necessary, the method will cache it itself
            return $this->{'get_'.$key}();

        } elseif(isset($this->info[$key])) {
            $return = $this->info[$key];

        } elseif(isset($this->repo[$key])) {
            $return = $this->repo[$key];

        } elseif(isset($this->log[$key])) {
            $return = $this->log[$key];

        } elseif(method_exists($this,'default_'.$key)) {
            return $this->{'default_'.$key}();
        }
        $this->$key = $return;
        return $return;
    }

    function __isset($key) {
        return $this->$key !== false;
    }

    /**
     * return same name as displayed by repo plugin at www.dokuwiki.org
     */
    protected function get_displayname() {
        $name = $this->id;
        if(!empty($this->base)) $name = $this->base;
        $this->displayname =  ucfirst($name).(($this->is_template) ? ' template' : ' plugin');
        return $this->displayname;
    }

    /**
     * return description from *.info.txt (if no repo info was found)
     */
    protected function default_description() {
        $this->description = "";
        if(!empty($this->desc)) $this->description = $this->desc;
        return $this->description;
    }

    /**
     * check if update available by comparing repository (lastupdate) with local info (date/install_date)
     */
    function get_update_available() {
        $this->update_available = false;
        if (!$this->is_installed) return false;
        if (empty($this->lastupdate)) return false;
        if ($this->lastupdate <= $this->date) return false;
        if ($this->lastupdate <= $this->install_date) return false;

        $this->update_available = true;
        return true;
    }

    function get_install_date() {
        $time = '';
        if(!empty($this->updated)) {
            $time = $this->updated;
        } elseif(!empty($this->installed)) {
            $time = $this->installed;
        }
        $this->install_date = date('Y-m-d',strtotime($time));
        return $this->install_date;
    }

    function get_is_disabled() {
        return !$this->is_enabled;
    }

    /**
     * no disk actions allowed on protected plugins
     */
    function get_no_fileactions_allowed() {
        $this->no_fileactions_allowed = true;

        if(!$this->is_writable) return true;
        if($this->is_bundled) return true;
        if($this->is_protected) return true;
        if($this->is_gitmanaged) return true;
        if (!$this->manager->getConf('allow_download')) return true;

        $this->no_fileactions_allowed = false;
        return false;
    }

    /**
     * capabilities, used in combination with $actions_list
     */
    abstract function can_select();

    final function can_info() {
        return true;
    }

    function can_enable() {
        return false;
    }

    function can_disable() {
        return false;
    }

    function can_download() {
        return false;
    }

    function can_download_disabled() {
        return false;
    }

    function can_download_dependency() {
        return false;
    }

    function can_update() {
        if(!$this->update_available) return false;
        if(empty($this->downloadurl)) return false;
        if($this->no_fileactions_allowed) return false;
        return true;
    }

    function can_delete() {
        if($this->no_fileactions_allowed) return false;
        return true;
    }

    function can_reinstall() {
        if($this->update_available) return false;
        if(empty($this->downloadurl)) return false;
        if($this->no_fileactions_allowed) return false;
        return true;
    }

    /**
     * failure reasons, used in combination with $possible_errors
     */
    function missing_dlurl () {
        if(!empty($this->downloadurl)) return false;
        //bundled plugins should not have download urls
        //no point saying the same thing twice
        if($this->is_bundled) return false;
        // no action should be allowed on protected plugins
        if($this->is_protected) return false;
        return true;
    }

    function missing_dependency() {
        if(!empty($this->relations['depends']['id'])) {
            foreach((array) $this->relations['depends']['id'] as $depends) {
                $key = (stripos($depends,'template:')===0) ? 'template' : 'plugin';
                if(!in_array(str_replace('template:','',$depends),$this->manager->{$key.'_list'}))
                    $missing[] = $depends;
            }
            if(!empty($missing)) {
                $this->missing_dependency = $missing;
                return true;
            }
        }
        return false;
    }

    function not_writable() {
        return (!$this->is_writable && !$this->is_bundled && !$this->is_protected);
    }

    function bundled() {
        return $this->is_bundled;
    }

    function installed() {
        return $this->is_installed;
    }

    function gitmanaged() {
        return $this->is_gitmanaged;
    }

    function has_conflicts() {
        if(!empty($this->relations['conflicts']['id'])) {
            $key = ($this->is_template) ? 'template' : 'plugin';
            $installed_conflicts = array_intersect($this->manager->{$key.'_list'},(array)$this->relations['conflicts']['id']);
            if(!empty($installed_conflicts)) {
                $this->has_conflicts = $installed_conflicts;    
                return true;
            }
        }
        return false;
    }

    /**
     * error notice when plugin/template folder doesn't match *.info.txt data (overridden in pm_search_single_lib)
     */
    function wrong_folder() {
        if(!empty($this->info['base']) && $this->info['base'] != $this->id) return true;
        return false;
    }

    /**
     * warning notice for url changed since last install/update (overridden in pm_search_single_lib)
     */
    function url_changed() {
        if(empty($this->repo['downloadurl']) || empty($this->log['downloadurl'])) return false;
        if($this->repo['downloadurl'] == $this->log['downloadurl']) return false;
        return true;
    }

    function highlight() {
        if($this->manager->showinfo == $this->id) return true;
        return false;
    }


}
