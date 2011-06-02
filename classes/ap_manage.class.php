<?php

class ap_manage {

    var $manager = NULL;
    var $lang = array();
    var $plugin = '';
    var $downloaded = array();
    var $repo_cache = NULL;
    
    function __construct($manager, $plugin) {
        $this->manager = $manager;
        $this->plugin = $plugin;
        $this->lang = $manager->lang;
        $this->repo_cache = new cache('plugin_manager', 'sa');
        $this->check_load_cache();
    }

    function process() {
        return '';
    }

    function html() {
        global $ID,$lang;
        $this->html_menu();
        print $this->manager->locale_xhtml('admin_plugin');
        ptln('<div class="common">');
        ptln('  <h2>Search for a new plugin</h2>');//TODO Add language
        ptln('  <form action="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search')).'" method="post">');
        ptln('    <fieldset class="hidden">',4);
        formSecurityToken();
        ptln('    </fieldset>');
        ptln('    <fieldset>');
        ptln('      <legend>'.$lang['btn_search'].'</legend>');
        ptln('      <label for="dw__search">'.$lang['btn_search'].'<input name="term" id="dw__search" class="edit" type="text" maxlength="200" /></label>');
        ptln('      <input type="submit" class="button" name="fn[search]" value="'.$lang['btn_search'].'" />');
        ptln('    </fieldset>');
        ptln('  </form>');
        ptln('</div>');
        /**
         * List plugins
         */
            ptln('<h2>'.$this->lang['manage'].'</h2>');
            ptln('<form action="'.wl($ID,array('do'=>'admin','page'=>'plugin')).'" method="post" class="plugins">');
            ptln('  <fieldset class="hidden">');
            formSecurityToken();
            ptln('  </fieldset>');
            
            $this->html_pluginlist();

            ptln('  <fieldset class="buttons">');
            ptln('    <input type="submit" class="button" name="fn[enable]" value="'.$this->lang['btn_enable'].'" />');
            ptln('  </fieldset>');

            //            ptln('  </div>');
            ptln('</form>');
        //end list plugins
    }

    // build our standard menu
    function html_menu() {
        global $lang;
        ptln('<div class="pm_menu">');
		ptln('    <ul>');
		ptln('	    <li class="'.(($this->manager->cmd == "plugin")? " selected": "bar").'" ><a href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'plugin')).'">'.rtrim($this->lang['plugin'],":").'</a></li>');
		ptln('	    <li class="'.(($this->manager->cmd == "template")? " selected": "bar").'"><a href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'template')).'">'.$this->lang['template'].'</a></li>');
		ptln('	    <li class="'.(($this->manager->cmd == "search")? " selected": "bar").'"><a href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search')).'">'.$lang['btn_search'].'</a></li>');
		ptln('    </ul>');
        ptln('</div>');
    }

    function html_pluginlist() {
        global $ID;
        global $plugin_protected;

        foreach ($this->manager->plugin_list as $plugin) {

            $disabled = plugin_isdisabled($plugin);
            $protected = in_array($plugin,$plugin_protected);

            $checked = ($disabled) ? '' : ' checked="checked"';
            $check_disabled = ($protected) ? ' disabled="disabled"' : '';

            // determine display class(es)
            $class = array();
            if (in_array($plugin, $this->downloaded)) $class[] = 'new';
            if ($disabled) $class[] = 'disabled';
            if ($protected) $class[] = 'protected';

            $class = count($class) ? ' class="'.join(' ', $class).'"' : '';

            ptln('    <fieldset'.$class.'>');
            ptln('      <legend>'.$plugin.'</legend>');
            ptln('      <input type="checkbox" class="enable" name="enabled[]" value="'.$plugin.'"'.$checked.$check_disabled.' />');
            ptln('      <h3 class="legend">'.$plugin.'</h3>');

            $this->html_button($plugin, 'info', false, 6);
            if (in_array('settings', $this->manager->functions)) {
                $this->html_button($plugin, 'settings', !@file_exists(DOKU_PLUGIN.$plugin.'/settings.php'), 6);
            }
            $this->html_button($plugin, 'update', !$this->plugin_readlog($plugin, 'url'), 6);
            $this->html_button($plugin, 'delete', $protected, 6);

            ptln('    </fieldset>');
        }
    }

    function html_button($plugin, $btn, $disabled=false, $indent=0) {
        $disabled = ($disabled) ? 'disabled="disabled"' : '';
        ptln('<input type="submit" class="button" '.$disabled.' name="fn['.$btn.']['.$plugin.']" value="'.$this->lang['btn_'.$btn].'" />',$indent);
    }

    /**
     *  Refresh plugin list
     */
    function refresh() {
        global $config_cascade;

        // expire dokuwiki caches
        // touching local.php expires wiki page, JS and CSS caches
        @touch(reset($config_cascade['main']['local']));

        // update latest plugin date - FIXME
        global $ID;
        send_redirect(wl($ID,array('do'=>'admin','page'=>'plugin'),true, '&'));
    }

    /**
     * Write a log entry to the given target directory
     */
    function plugin_writelog($target, $cmd, $data) {

        $file = $target.'/manager.dat';

        switch ($cmd) {
            case 'install' :
                $url = $data[0];
                $date = date('r');
                if (!$fp = @fopen($file, 'w')) return;
                fwrite($fp, "installed=$date\nurl=$url\n");
                fclose($fp);
                break;

            case 'update' :
                $date = date('r');
                if (!$fp = @fopen($file, 'a')) return;
                fwrite($fp, "updated=$date\n");
                fclose($fp);
                break;
        }
    }

    function plugin_readlog($plugin, $field) {
        static $log = array();
        $file = DOKU_PLUGIN.plugin_directory($plugin).'/manager.dat';

        if (!isset($log[$plugin])) {
            $tmp = @file_get_contents($file);
            if (!$tmp) return '';
            $log[$plugin] = & $tmp;
        }

        if ($field == 'ALL') {
            return $log[$plugin];
        }

        $match = array();
        if (preg_match_all('/'.$field.'=(.*)$/m',$log[$plugin], $match))
            return implode("\n", $match[1]);

        return '';
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
     * checks to see if a valid cache exists, if it doesnot, makes one...
     */
    function check_load_cache() {
        if(!$this->repo_cache->useCache(array('age'=>172800)))
            $this->reload_cache();
    }
    
    /**
     * Downloads and reloads cache. may be moving to serialized result directly from server would work better?
     * FIXME Last updated time to prevent calls on every pageload (offline / behind firewalls)
     */
    function reload_cache() {
        $dhc = new DokuHTTPClient();
        $data = $dhc->get('http://www.dokuwiki.org/lib/plugins/pluginrepo/repository.php');
        unset($dhc);
        if($data) {
            try {
                if(class_exists('SimpleXMLElement')) {
                    $obj = new SimpleXMLElement($data);
                    $array = $this->obj_array($obj);
                    unset($obj);
                    $data = $array['plugin'];
                }
                else {
                    $array = $this->xml_array($data);
                    $data = $array['repository']['plugin'];
                }
                $this->repo_cache->storeCache(serialize($data));
            }
            catch(Exception $e) {
                // do some debugging actions if necessary?
            }
        }
    }
    
    /**
     * Converts objects to arrays. may be should be kept under parseutils??
     */
    function obj_array($obj) {
        $data = array();
        if (is_object($obj))
            $obj = get_object_vars($obj);
        if (is_array($obj) && count($obj)) {
            foreach ($obj as $index => $value) {
                if (is_object($value) || is_array($value))
                    $value = $this->obj_array($value);
                $data[$index] = $value;
            }
        }
        return count($data)? $data : null;
    }
    
    /**
     * Converts XML to arrays. may be should be kept under parseutils?? 
     * FIXME (only if we want a generic function) Doesnt support attributes yet
     */
    function xml_array ($string) {
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser,$string, $struct);
        xml_parser_free($parser);
        if(!is_array($struct))
            throw new Exception('Repository XML unformatted'); // FIXME Add language
        $xml = array();
        $levels = array();
        $current = &$xml;
        foreach($struct as $single) {
            $value = null;
            extract($single);
            if(in_array($type,array('open','complete'))) {
                $levels[$level-1] = &$current;
                if(!@array_key_exists($tag, $current)) {
                    $current[$tag] = $value;
                    $current = &$current[$tag];
                }
                else {
                    if(is_array($current[$tag]) && array_key_exists(0,$current[$tag]))
                        $current[$tag][] = $value;
                    else
                        $current[$tag] = array($current[$tag],$value);
                    $current = &$current[$tag][count($current[$tag])-1];
                }
            }
            if(in_array($type,array('close','complete'))) {
                $current = &$levels[$level-1];
            }

        }
        return $xml;
    }
}
