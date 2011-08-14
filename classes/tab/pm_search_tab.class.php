<?php
class pm_search_tab extends pm_base_tab {

    var $term = NULL;
    var $filters = array();
    var $search_result = array();
    var $filtered_repo = NULL;
    var $extra = NULL;
    var $versions = array();
    var $actions_list = array();
    var $search_types = array();

    function process() {
        $doku = getVersionData();
        $this->doku_version = $doku['date'];
        $this->clean_repo();
        $this->actions_list = array(
                'download'=>$this->manager->getLang('btn_download'),
                'download_disabled'=>$this->manager->getLang('btn_disdown'),
                );
        $this->possible_errors = array(
                'bundled' => $this->manager->getLang('bundled'),
                'has_conflicts' => $this->manager->getLang('conflicts'),
                'missing_dependency' => $this->manager->getLang('depends'),
                'missing_dlurl' => $this->manager->getLang('no_url'),
                'installed' =>$this->manager->getLang('already_installed')
                );
        //https://github.com/piyushmishra/dokuwiki/commit/7c4ad50ccd13707eab764363e275286bab428435#commitcomment-529776
        $this->search_types = array(
            ''=>$this->manager->getLang('all'),
            'Syntax'=>$this->manager->getLang('syntax')." (Syntax)",
            'Admin'=>$this->manager->getLang('admin')." (Admin)",
            'Action'=>$this->manager->getLang('action')." (Action)",
            'Render'=>$this->manager->getLang('render')." (Render)",
            'Helper'=>$this->manager->getLang('helper')." (Helper)",
            'Template'=>$this->manager->getLang('template')." (Template)"
            );
        $this->filters = array('id' => NULL,'name' => NULL,'description' => NULL, 'type' => NULL, 'tag' =>NULL, 'author' => NULL);

        if(!empty($_REQUEST['term']) > 0) {
            $this->term = $_REQUEST['term'];
            //add parsing for key=value based extras
        }
        if(!empty($_REQUEST['type'])) {
            $this->extra['type'] = $_REQUEST['type'];
        }
        if($this->term !== null || $this->extra !== null ) {
            if($this->term === null) $this->term = " ";
            if($this->manager->repo !== null)
                $this->lookup();
        }
    }

    function html() {
        $this->html_menu();
        $this->render_search('install__search', $this->manager->getLang('search_plugin'),$this->term,$this->search_types);

        if(is_array($this->search_result) && count($this->search_result)) {
            $type = (!empty($this->extra['type']) && $this->extra['type'] == "Template" )? 'template': 'plugin' ;
            $list = new pm_plugins_list_lib($this->manager,'search__result',$this->actions_list,$this->possible_errors,$type);
            $list->add_header(sprintf($this->manager->getLang('search_results'),hsc($this->term)));
            $list->start_form();
            foreach($this->search_result as $result) {
                foreach($result as $info) {
                    $info = $this->_info_list($info['id']);
                    $list->add_row($info);
                }
            }
            $list->end_form(array_keys($this->actions_list));
            $list->render();
        } elseif(!is_null($this->term)) {
            $no_result = new pm_plugins_list_lib($this->manager,'no__result');
            $no_result->add_header(sprintf($this->manager->getLang('not_found'),hsc($this->term)));
            $url = wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search'));
            $no_result->add_p(sprintf($this->manager->getLang('no_result'),$url,$url));
            $no_result->render();
        } else {
            $full_list = new pm_plugins_list_lib($this->manager,'browse__list',$this->actions_list,$this->possible_errors);
            $full_list->add_header($this->manager->getLang('browse'));
            $full_list->start_form();
            foreach($this->filtered_repo as $info) {
                $info = $this->_info_list($info['id'],'search');
                $full_list->add_row($info);
            }
            $full_list->end_form(array_keys($this->actions_list));
            $full_list->render();
        }
    }

    function check_writable() {
        if(!is_writable(DOKU_INC.'lib/tpl/')) {
            msg($this->manager->getLang('not_writable')." ".DOKU_INC.'lib/tpl/',-1);
        }
        if(!is_writable(DOKU_PLUGIN)) {
            msg($this->manager->getLang('not_writable')." ".DOKU_PLUGIN,-1);
        }
    }

    function _info_list($single) {
        return parent::_info_list($single,'search');
    }

    protected function clean_repo() {
        $this->filtered_repo = array_filter($this->manager->repo,array($this,'filter_clean'));
        
    }
    /**
     * Looks up the term in the repository cache according to filters set. Basic searching.
     * TODO advanced searching options (case-sensitive, for exact term etc) is it necessary??
     * TODO assign weights to matches, like id matches highest in ordering
     */
    protected function lookup() {
        foreach ($this->filtered_repo as $single) {
            $matches = array_filter($single,array($this,'search'));
            if(count($matches)) {
                $count = count(array_intersect_key($this->filters,$matches));
                if($count && $this->check($single)) {
                    // increase weight for id match
                    if(stripos($single['id'],$this->term)!==false) $count += 5;
                    //increase weight for name match
                    if(stripos($single['name'],$this->term)!==false) $count += 3;
                    $this->search_result[$count][$single['id']] = $single;
                }
            }
        }
        return krsort($this->search_result);
    }

    /**
     * Search for the term in every plugin and return matches.
     */
    protected function search($haystack) {
        if(is_array($haystack) && array_key_exists('tag',$haystack) && in_array('tag',$this->filters)) {
            return (bool) count(array_filter((array)$haystack['tag'],array($this,'search')));
        }        
        return @stripos($haystack,$this->term) !== false;
    }

    /**
     * Checks to figure out if a plugin should be searched, 
     * based on some settings, version, current context(may be?)
     */
    protected function check($plugin) {
        //$version_data = getVersionData();
        //print_r($this->extra);
        if(is_array($this->extra) && count($this->extra))
            foreach($this->extra as $index => $value)
                if(count($value)) {
                    if($index == 'type') {
                        if(strlen($value) && stripos($plugin['type'],$value) === false) return false;
                    }
                    elseif($index == 'tag') { // Tag based left here for future use
                        foreach($value as $tag)
                            if(strlen($tag))
                                if(@in_array(trim($tag),(array)$plugin['tags']['tag'])===false) return false;
                    }elseif(!(array_key_exists($index,$plugin) && $plugin[$index] == $value)) return false;
                }
        //All tests passed
        return true;
    }

    /**
     * Used to filter BEFORE the repo is searched on. only remove very very important ones
     */
    protected function filter_clean($plugin) {
        //Check for security issue
        if(!empty($plugin['securityissue'])) return false;
        if(@in_array('!obsolete',(array)$plugin['tags']['tag'])) return false;
        if(@in_array('!bundled',(array)$plugin['tags']['tag'])) return false;
        //all tests passed
        return true;
    } 
}
