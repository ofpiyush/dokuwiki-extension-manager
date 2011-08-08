<?php

abstract class ap_manage {

    var $manager = NULL;
    var $lang = array();
    var $plugin = '';
    var $downloaded = array();
    var $repo_cache = NULL;
    var $tpl_dir = NULL;
    var $repo_url = 'http://www.dokuwiki.org/lib/plugins/pluginrepo/repository.php?showall=yes&includetemplates=yes';
    protected $_bundled = array();

    function __construct(DokuWiki_Admin_Plugin $manager) {
        $this->_bundled = array('acl','plugin','config','info','usermanager','revert','popularity','safefnrecode','default');
        $this->tpl_dir = DOKU_INC.'lib/tpl/';
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
            $tabs_array = array(
                'plugin' => rtrim($this->lang['plugin'],":"),
                'template' =>$this->lang['template'],
                'search' =>"Install"
                );
            $selected = array_key_exists($this->manager->cmd,$tabs_array)? $this->manager->cmd : 'plugin' ;
            ptln('<div class="pm_menu">');
		    ptln('    <ul>');
		    foreach($tabs_array as $tab =>$text)
		        ptln('	    <li class="'.(($tab == $selected)? "selected": "notsel").'" ><a href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>$tab)).'">'.$text.'</a></li>');
		    ptln('    </ul>');
            ptln('</div>');
    }

    protected function render_search($id,$head,$value = '',$type = null) {
        global $lang,$ID;
        ptln('<div class="common">');
        ptln('  <h2>'.$head.'</h2>');
        $search_form = new Doku_Form($id);
        $search_form->startFieldset($lang['btn_search']);
        $search_form->addElement(form_makeTextField('term',$value,$lang['btn_search'],'pm__sfield'));
        $search_form->addHidden('page','plugin');
        $search_form->addHidden('tab','search');
        $search_form->addHidden('fn','search');
        if($type !== null)
            if(is_array($type) && count($type))
                $search_form->addElement(form_makeMenuField('type',$type,'',''));
            else
                $search_form->addHidden('type',$type);
        $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
        $search_form->endFieldset();
        $search_form->printForm();
        ptln('</div>');
    }

    function make_action($action,$plugin,$value,$template = false) {
        global $ID;
        $params = array(
            'do'=>'admin',
            'page'=>'plugin',
            'fn'=>'multiselect',
            'action'=>$action,
            'checked[]'=>$plugin,
            'sectok'=>getSecurityToken()
        );
        if($template) $params['template'] = 'template';
        $url = wl($ID,$params);
        return '<a href="'.$url.'" title="'.$url.'">'.$value.'</a>';
    }
    /**
     *  Refresh plugin list
     */
    function refresh($tab = "plugin") {
        global $config_cascade;

        // expire dokuwiki caches
        // touching local.php expires wiki page, JS and CSS caches
        @touch(reset($config_cascade['main']['local']));

        // update latest plugin date - FIXME
        global $ID;
        send_redirect(wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>$tab),true, '&'));
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
                fwrite($fp, "installed=$date".PHP_EOL."downloadurl=$url".PHP_EOL);
                fclose($fp);
                break;

            case 'update' :
                $date = date('r');
                if (!$fp = @fopen($file, 'a')) return;
                fwrite($fp, "updated=$date".PHP_EOL);
                fclose($fp);
                break;                
        }
    }

    function fetch_log($path,$field = 'ALL') {
        static $log = array();
        $hash = md5($path);

        if (!isset($log[$hash])) {
            $file = @file($path.'manager.dat');
            if(empty($file)) return false;
            foreach($file as $line) {
                $line = explode('=',trim($line,PHP_EOL));
                $line = array_map('trim', $line);
                if($line[0] == 'url') $line[0] = 'downloadurl';
                $log[$hash][$line[0]] = $line[1];
            }
        }

        if ($field == 'ALL') {
            return $log[$hash];
        }

        if(!empty($log[$hash][$field])) return $log[$hash][$field];
        return false;
    }

    /**
     * return a list (name & type) of all the component plugins that make up this plugin
     *
     * @todo can this move to pluginutils?
     */
    function get_plugin_components($plugin) {

        global $plugin_types;
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

        return $components;
    }

    /**
     * Read info and return an array compatible with plugins_list table
     */
    protected function _info_list($index,$type = "plugin") {
        $info_autogenerate = false;
        $path = ($type == "plugin") ? DOKU_PLUGIN.plugin_directory($index).'/': DOKU_INC."lib/tpl/$index/";
        $info_path = $path.$type.'.info.txt';
        $return = array('id'=>$index,'name' => $index,'base'=>$index);
        if(@file_exists($info_path)) {
            $return = array_merge($return,confToHash($info_path));
        } elseif($type == 'plugin') {
            $components = $this->get_plugin_components($index);
            if(!empty($components)) {
                $obj = plugin_load($components[0]['type'],$components[0]['name'],false,true);
                if(!empty($obj)) {
                    $obj_info = $obj->getInfo();
                    $return = array_merge($return,$obj_info);
                    if(empty($obj_info['base'])) $obj_info['base'] = $plugin;
                    $this->info_autogen($info_path,$obj_info);
                }
                unset($obj);
            }
        } else {
            $info_autogenerate = true;
        }

        $repo_key = ($type == 'template') ? 'template:'.$return['base'] : $return['base'];
        if(!empty($this->repo[$repo_key])) {
            $return = array_merge($return,$this->repo[$repo_key]);
        }
        if(!empty($return['desc'])) {
            $return['description'] = $return['desc'];
        }
        $return['id'] = $index;
        $log = $this->fetch_log($path);
        if(!empty($log)) {
            $return = array_merge($log,$return);
        } elseif(!empty($return['downloadurl'])) {
            $this->plugin_writelog($path,'install',array($return['downloadurl']));
        }
        if($info_autogenerate && !empty($return['description'])) {
            $this->info_autogen($info_path,$return);
        }
        return $return;
    }


    /**
     * Auto generate plugin and template info.txt
     */
    function info_autogen($file,$return) {
        $info = "";
        if (!$fp = @fopen($file, 'w')) return false;
        foreach(array('base','author','email','date','name','desc','url') as $index) {
            if(!empty($return[$index])) $info.= $index." ".$return[$index]."\n";
        }
        if(empty($return['desc']) && !empty($return['description'])) {
            $info.='desc '.$return['description']."\n";
        }
        if(empty($return['url']) && !empty($return['dokulink'])) {
            $info.='url http://www.dokuwiki.org/'.$return['dokulink']."\n";
        }
        fwrite($fp, $info);
        fclose($fp);
        msg("Auto generated and saved info for ".$return['base'],2);
        return true;
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
        $data = $dhc->get($this->repo_url);
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
        } else {
            $this->repo_cache->storeCache(serialize(array()));
            msg("There was an error retrieving the plugin list from the dokuwiki server, please force reload later", -1);
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
    //sorting based on name
    protected function _sort($a,$b) {
        return strcmp($a['name'],$b['name']);
    }
}
