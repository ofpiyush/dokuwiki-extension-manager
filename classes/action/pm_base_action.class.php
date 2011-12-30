<?php
/**
 * Base action class, common functions for all child actions
 * @author Piyush Mishra <me@piyushmishra.com>
 */
abstract class pm_base_action {
    
    final function __construct(admin_plugin_extension $manager) {
        $this->plugin = $manager->plugin;
        $this->manager = $manager;
        $this->act();
    }

    /**
     * takes the requested action. to be declared by the child classes
     */
    abstract function act();

    /**
     *  Refresh plugin list
     */
    function refresh($tab = "plugin",$extra =false,$anchor = '') {
        global $config_cascade;

        // expire dokuwiki caches
        // touching local.php expires wiki page, JS and CSS caches
        @touch(reset($config_cascade['main']['local']));

        global $ID;
        $params =array('do'=>'admin','page'=>'extension','tab'=>$tab);
        if(!empty($extra)) $params = array_merge($params,$extra);
        if(!empty($anchor)) $anchor = "#".$anchor;
        send_redirect(wl($ID,$params,true, '&').$anchor);
    }

    /**
     * delete, with recursive sub-directory support
     */
    function dir_delete($path) {
        if (!is_string($path) || $path == "") return false;

        if (is_dir($path) && !is_link($path)) {
            if (!$dh = @opendir($path)) return false;

            while ($f = readdir($dh)) {
                if ($f == '..' || $f == '.') continue;
                $this->dir_delete("$path/$f");
            }

            closedir($dh);
            return @rmdir($path);
        } else {
            return @unlink($path);
        }

        return false;
    }

    /**
     * if $results are available, call relevant say_* functions to show the results of an action
     */
    protected function show_results() {
        if(is_array($this->result) && count($this->result)) {
            foreach($this->result as $outcome => $changed_plugins)
                if(is_array($changed_plugins) && count($changed_plugins))
                    array_walk($changed_plugins,array($this,'say_'.$outcome));
        }
    }
}
