<?php

abstract class ap_manage {

    var $manager = NULL;
    var $lang = array();
    var $plugin = '';
    var $downloaded = array();
    var $repo_cache = NULL;
    protected $_bundled = array('acl','plugin','config','info','usermanager','revert','popularity','safefnrecode');

    function __construct(DokuWiki_Admin_Plugin $manager) {
        global $plugin_bundled;
        if(is_array($plugin_bundled) && count($plugin_bundled))
            $this->_bundled = $plugin_bundled;
        else
            $plugin_bundled = $this->_bundled;
        $this->manager = $manager;
        $this->plugin = $manager->plugin;
        $this->lang = $manager->lang;
        $this->repo_cache = new cache('plugin_manager', '.sa');
        $this->check_load_cache();
        $this->repo = $this->fetch_cache();
    }

    abstract function process();

    abstract function html();

    // build our standard menu
    function html_menu() {
        global $ID;
            $tab = (in_array($this->manager->cmd,array('plugin','template','search')))? $this->manager->cmd : 'plugin' ;
            ptln('<div class="pm_menu">');
		    ptln('    <ul>');
		    ptln('	    <li class="'.(($tab == "plugin")? " selected": "bar").'" ><a href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'plugin')).'">'.rtrim($this->lang['plugin'],":").'</a></li>');
		    ptln('	    <li class="'.(($tab == "template")? " selected": "bar").'"><a href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'template')).'">'.$this->lang['template'].'</a></li>');
		    ptln('	    <li class="'.(($tab == "search")? " selected": "bar").'"><a href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search')).'">Install</a></li>');
		    ptln('    </ul>');
            ptln('</div>');
    }

    protected function make_title($info) {
        if(array_key_exists('dokulink',$info) && strlen($info['dokulink'])) {
            $url ="http://dokuwiki.org/".$info['dokulink'];
            return '<a class="interwiki iw_doku" title="'.$url.'" href="'.$url.'">'.hsc($info['name']).'</a>';
        }
        if(array_key_exists('url',$info) && strlen($info['url'])) {
            return  '<a class="urlextern" href="'.$info['url'].'" title="'.$info['url'].'" >'.hsc($info['name']).'</a>';
        }
        return  hsc($info['name']);
    }

    protected function make_url($action,$plugin) {
        global $ID;
        return wl($ID,array('do'=>'admin','page'=>'plugin','fn'=>'multiselect','action'=>$action,'checked[]'=>$plugin,'sectok'=>getSecurityToken()));
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

    function fetch_cache() {
        return @unserialize($this->repo_cache->retrieveCache());
    }

    /**
     * Downloads and reloads cache. may be moving to serialized result directly from server would work better?
     * FIXME Last updated time to prevent calls on every pageload (offline / behind firewalls)
     */
    function reload_cache() {
        $dhc = new DokuHTTPClient();
        $data = $dhc->get('http://www.dokuwiki.org/lib/plugins/pluginrepo/repository.php?includetemplates=yes');
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
                foreach($data as $single)
                    $final[$single['id']] = $single;
                unset($data);
                $this->repo_cache->storeCache(serialize($final));
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
