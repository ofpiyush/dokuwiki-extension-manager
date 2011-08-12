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

abstract class base_single {
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
     * If plugin/template bundled or not
     * @var bool
     */
    var $is_bundled = false;

    /**
     * if it is compatible with the current DokuWiki version (should have a "no" state)
     * current states true = yes false = may be
     * @var bool (should be string later)
     */
    var $is_compatible = false;

    /**
     * If it is a template or a plugin
     * @var bool
     */
    var $is_template = false;

    var $repo = array();

    var $info = array();

    var $manager = array();

    final function __construct($base,$dirname) {
        $this->id = $dirname;
        $this->b = $base;
        $this->setup();
    }

    abstract protected function setup();

    function __get($key) {
        $return = false;
        // do not cache anything returned from a method
        //if its necessary, the method will cache it itself
        if(method_exists($this,'get_'.$key)) { 
            return $this->{'get_'.$key}();
        } elseif(isset($this->repo[$key])){
             $return = $this->repo[$key];
        }elseif(isset($this->info[$key])){ $return = $this->info[$key];
        }elseif(isset($this->manager[$key])){ $return = $this->manager[$key];
        }elseif(method_exists($this,'default_'.$key)){ return $this->{'default_'.$key}();}
        //$this->$key = $return;
        return $return;
    }

    function __isset($key) {
        return $this->$key !== false;
    }

    /**
     * @return bool if the plugin/template can be updated
     */
    function can_update() {
        if(empty($this->downloadurl)) return false;
        if(!$this->is_writable) return false;
        if($this->is_bundled) return false;
        return true;
    }

    function can_delete() {
        if(!$this->is_writable) return false;
        if($this->is_bundled) return false;
        return true;
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
            return  $this->b->get_lang('bundled').'<br /> <em>('.$version['date'].')</em>';
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
            $this->version = $this->b->get_lang('unknown');
            if($time !== 0) $this->version .= '<br /> <em>('.date('Y-m-d',strtotime($time)).')</em>';
        }

        return $this->version;
    }

    protected function default_description() {
        $this->description ="";
        if(!empty($this->desc)) $this->description = $this->desc;
        return $this->description;
    }
    protected function default_name() {
        return $this->base;
    }

    protected function default_base() {
        return $this->id;
    }

}
