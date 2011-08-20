<?php
/**
 * Detailed info of a single plugin
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


    var $repo = array();

    var $info = array();

    var $log = array();

    final function __construct(admin_plugin_extension $base,$dirname) {
        $this->id = $dirname;
        $this->manager = $base;
    }

    abstract function can_select();

    function __get($key) {
        $return = false;
        // do not cache anything returned from a method
        //if its necessary, the method will cache it itself
        if(method_exists($this,'get_'.$key)) { 
            return $this->{'get_'.$key}();
        } elseif(isset($this->repo[$key])){
             $return = $this->repo[$key];
        }elseif(isset($this->info[$key])){ $return = $this->info[$key];
        }elseif(isset($this->log[$key])){ $return = $this->log[$key];
        }elseif(method_exists($this,'default_'.$key)){ return $this->{'default_'.$key}();}
        $this->$key = $return;
        return $return;
    }

    function __isset($key) {
        return $this->$key !== false;
    }

    /**
     * @return bool if the plugin/template can be updated
     */
    function can_update() {
        if(empty($this->newversion)) return false;
        if(empty($this->downloadurl)) return false;
        if(!$this->is_writable) return false;
        if($this->is_bundled) return false;
        // no action should be allowed on protected plugins
        if($this->is_protected) return false;
        return true;
    }

    function can_delete() {
        if(!$this->is_writable) return false;
        if($this->is_bundled) return false;
        // no action should be allowed on protected plugins
        if($this->is_protected) return false;
        return true;
    }

    function can_reinstall() {
        if(empty($this->downloadurl)) return false;
        if(!$this->is_writable) return false;
        if($this->is_bundled) return false;
        // no action should be allowed on protected plugins
        if($this->is_protected) return false;
        if(!empty($this->newversion)) return false;
        return true;
    }

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

    function wrong_folder() {
        return false;
    }
    function highlight() {
        if($this->manager->showinfo == $this->id) return true;
        return false;
    }
    function not_writable() {
        return (!$this->is_writable && !$this->is_bundled && !$this->is_protected);
    }
    function bundled() {
        return $this->is_bundled;
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

    function get_is_disabled() {
        return !$this->is_enabled;
    }
    protected function default_description() {
        $this->description ="";
        if(!empty($this->desc)) $this->description = $this->desc;
        return $this->description;
    }
    protected function default_name() {
        return $this->id;
    }

    final function can_info() { return true;}
}
