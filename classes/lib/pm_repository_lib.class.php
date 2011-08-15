<?php
class pm_repository_lib {
    private $repo = null;
    var $manager = null;
    var $repo_cache = NULL;
    var $repo_url = 'http://www.dokuwiki.org/lib/plugins/pluginrepo/repository.php?showall=yes&includetemplates=yes';

    function __construct(admin_plugin_plugin $manager) {
        $this->repo_cache = new cache('plugin_manager', '.sa');
        $this->check_load();
        $this->repo = $this->fetch();
        $this->manager = $manager;
    }
    /**
     * checks to see if a valid cache exists, if it doesnot, makes one...
     */
    function check_load() {
        if(!$this->repo_cache->useCache(array('age'=>172800)))
            $this->reload();
    }

    function fetch() {
        return @unserialize($this->repo_cache->retrieveCache());
    }

    function get() {
        return $this->repo;
    }
    /**
     * Downloads and reloads cache. may be moving to serialized result directly from server would work better?
     */
    function reload() {
        $error = true;
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
                $error = false;
            }
            catch(Exception $e) {
                msg($e->getMessage(), -1);
            }
        }
        if($error) {
            $this->repo_cache->storeCache(serialize(array()));
            msg($this->manager->getLang('repocache_error'), -1);
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
     */
    function xml_array ($string) {
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser,$string, $struct);
        xml_parser_free($parser);
        if(!is_array($struct))
            throw new Exception($this->manager->getLang('repoxml_error'));
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
