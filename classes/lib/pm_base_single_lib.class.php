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
 * @property string $version Version date string from various sources 
 * @property string $name Name of the plugin/template defaults to "id"
 * @property string $newversion if new version is available, its value
 * @property string $url for more info
 * @property string $screenshoturl Screenshoturl (from repo) Only applicable if $is_template = true;
 * @property string $repoid Repo id (from previous install or "base" match from *.info.txt)
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
     * Identifier (name of directory for installed plugins and "id" for repository plugins)
     * @property string $id
     */
    var $id = null;

    /**
     * If the manager can write in the folder
     * @var bool
     */ 
    var $is_writable = true;


    /**
     * if it is compatible with the current DokuWiki version (should have a "no" state)
     * current states true = yes false = may be
     * @var bool (should be string later)
     */
    var $is_compatible = false;
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
     * @property string downloadurl     URL used for last download              // TODO should be named url for backward compatibility?
     * @property string installed       RFC 2822 date time of installation
     * @property string updated         RFC 2822 date time of update
     */
    var $log = array();

    final function __construct(admin_plugin_extension $manager,$id) {
        $this->manager = $manager;
        $this->id = $id;
    }

    /**
     * Precedence order for accessing properties
     *      get(method) -> repository -> *.info.txt -> $log -> default(method)
     */
    function __get($key) {
        $return = false;

        if(method_exists($this,'get_'.$key)) { 
            // do not cache anything returned from a method
            // if its necessary, the method will cache it itself
            return $this->{'get_'.$key}();

        } elseif(isset($this->repo[$key])) {
            $return = $this->repo[$key];

        } elseif(isset($this->info[$key])) {
            $return = $this->info[$key];

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

    function get_update_available() {
        $this->update_available = false;
        if (!$this->is_installed) return false;
        if (empty($this->lastupdate)) return false;
        if ($this->lastupdate <= $this->date) return false;

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
        $this->install_date = $time;
        return $this->install_date;
    }

    function get_is_disabled() {
        return !$this->is_enabled;
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
        if($this->no_fileactions_allowed()) return false;
        return true;
    }

    function can_delete() {
        if($this->no_fileactions_allowed()) return false;
        return true;
    }

    function can_reinstall() {
        if($this->update_available) return false;
        if(empty($this->downloadurl)) return false;
        if($this->no_fileactions_allowed()) return false;
        return true;
    }

    /**
     * no disk actions allowed on protected plugins
     */
    protected function no_fileactions_allowed() {
        if(!$this->is_writable) return true;
        if($this->is_bundled) return true;
        if($this->is_protected) return true;
        return false;
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
     * Precedence Order
     * #1 DokuWiki version for bundled plugins and template
     * #2 "pm_date_version" from manager.dat(lastupdate string form repository while installing)
     * #3 "date" from *.info.txt
     */
    protected function get_version() {
        $time = 0;
        if($this->is_bundled) {
            $version = getVersionData();
            return  $this->manager->getLang('bundled').'<br /> <em>('.$version['date'].')</em>';
        } elseif(!empty($this->pm_date_version)) {
            $time = $this->pm_date_version;
            $this->version = $this->pm_date_version;
        } elseif(!empty($this->date)) {
            $time = $this->date;
            $this->version = $this->date;
        }elseif(!empty($this->updated)) {
            $time = $this->updated;
        } elseif(!empty($this->installed)) {
            $time = $this->installed;
        }
        if(!empty($this->lastupdate) && !empty($time) && $this->lastupdate > $time) {
            $this->newversion = $this->lastupdate;
        }
        if(empty($this->version)) {
            $this->version = $this->manager->getLang('unknown');
            if($time !== 0) $this->version .= '<br /> <em>('.date('Y-m-d',strtotime($time)).')</em>';
        }

        return $this->version;
    }

    protected function default_name() {
        return $this->id;
    }

    /**
     * wrong_folder (overridden in pm_search_single_lib)
     */
    function wrong_folder() {
        if(!empty($this->info['base']) && $this->info['base'] != $this->id) return true;
        return false;
    }

    function highlight() {
        if($this->manager->showinfo == $this->id) return true;
        return false;
    }


}
