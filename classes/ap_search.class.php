<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array();
    var $search_result = array();
    var $repo = NULL;
    var $filtered_repo = NULL;
    var $extra = NULL;
    var $versions = array();
    var $actions_list = array();
    var $search_types = array();

    function process() {
        if(empty($this->repo)) $this->refresh();
        $this->clean_repo();
        $this->actions_list = array(
                'download'=>$this->lang['btn_download'],
                'disdown'=>$this->lang['btn_disdown']
                );
        $this->search_types = array(
            ''=>$this->lang['all'],
            'Syntax'=>$this->lang['syntax'],
            'Admin'=>$this->lang['admin'],
            'Action'=>$this->lang['action'],
            'Renderer'=>$this->lang['renderer'],
            'Helper'=>$this->lang['helper'],
            'Template'=>$this->lang['template']
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
            if($this->repo !== null)
                $this->lookup();
        }
    }

    function html() {
        $this->html_menu();
        $this->render_search('install__search', $this->lang['search_plugin'],$this->term,$this->search_types);

        if(is_array($this->search_result) && count($this->search_result)) {
            ptln('<h2>'.hsc(sprintf($this->lang['search_results'],$this->term)).'</h2>');
            $list = new plugins_list($this,'search__result',$this->actions_list);
            foreach($this->search_result as $result)
                foreach($result as $info) {
                    $class = $this->get_class($info,'result');
                    $actions = $this->get_actions($info);
                    $checkbox = $this->get_checkbox($info);
                    $list->add_row($class,$info,$actions,$checkbox);
                }
            $list->render('PLUGIN_PLUGINMANAGER_RENDER_SEARCHRESULT');
        } elseif(!is_null($this->term)) {
            ptln('<h2>'.hsc(sprintf($this->lang['not_found'],$this->term)).'</h2>');
            $url = wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search'));
            ptln('<p>'.sprintf($this->lang['no_result'],$url,$url).'</p>');
        } else {
            ptln('<h2>'.$this->lang['browse'].'</h2>');
            $list = new plugins_list($this,'browse__list',$this->actions_list);
            foreach($this->filtered_repo as $info) {
                $class = $this->get_class($info,'all');
                $actions = $this->get_actions($info);
                $checkbox = $this->get_checkbox($info);
                $list->add_row($class,$info,$actions,$checkbox);
            }
            $list->render();
        }
    }

    protected function get_class($info,$class) {
        if(!empty($info['securityissue'])) $class .= ' secissue';
        if(!empty($this->extra['type']) && $this->extra['type'] == "Template" )
            $class .= " template";
        return $class;
    }

    protected function get_actions($info) {
        if(array_key_exists('downloadurl',$info) && !empty($info['downloadurl'])) {
            if(@stripos($info['type'],'Template')!==false) {
                $actions = $this->make_action('download',$info['id'],$this->lang['btn_disdown']);
            } else {
                $actions = $this->make_action('download',$info['id'],$this->lang['btn_download']);
                $actions .= ' | '.$this->make_action('disdown',$info['id'],$this->lang['btn_disdown']);
            }
        } else {
            $actions = $this->lang['no_url'];
        }
        return $actions;
    }

    protected function get_checkbox($info) {
        if(array_key_exists('downloadurl',$info) && !empty($info['downloadurl']))
            return array();
        return array('disabled'=>'disabled');
    }

    protected function clean_repo() {
        $this->filtered_repo = array_diff_key($this->repo,array_flip($this->manager->plugin_list));
        $this->filtered_repo = array_filter($this->filtered_repo,array($this,'filter_clean'));
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
                    if(stripos($single['id'],$this->term)) $count += 3;
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
        //all tests passed
        return true;
    } 
}
