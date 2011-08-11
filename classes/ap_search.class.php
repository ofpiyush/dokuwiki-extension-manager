<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array();
    var $result = array();
    var $repo = NULL;
    var $filtered_repo = NULL;
    var $extra = NULL;
    var $versions = array();
    var $actions_list = array();
    var $search_types = array();

    function process() {
        $this->clean_repo();
        $this->actions_list = array(
                'download'=>$this->lang['btn_download'],
                'disdown'=>'Download as disabled');//TODO add language
        $this->search_types = array(
            ''=>'All',//TODO add language
            'Syntax'=>'Syntax',//TODO add language
            'Admin'=>'Admin',//TODO add language
            'Action'=>'Action',//TODO add language
            'Renderer'=>'Renderer',//TODO add language
            'Helper'=>'Helper',//TODO add language
            'Template'=>'Template');//TODO add language
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

        ptln('<div class="common">');
        ptln('  <h2>'.$this->lang['download'].'</h2>');
        $url_form = new Doku_Form('install__url');
        $url_form->startFieldset($this->lang['download']);
        $url_form->addElement(form_makeTextField('url','',$this->lang['url'],'dw__url'));
        $url_form->addHidden('page','plugin');
        $url_form->addHidden('fn','download');
        $url_form->addElement(form_makeButton('submit', 'admin', $this->lang['btn_download'] ));
        $url_form->endFieldset();
        $url_form->printForm();
        ptln('</div>');

        $this->render_search('install__search', 'Search for a new plugin',$this->term,$this->search_types);

        if(is_array($this->search_result) && count($this->search_result)) {
            ptln('<h2>'.'Search results for "'.$this->term.'"</h2>');//TODO Add language
            $list = new plugins_list($this,'search_result',$this->actions_list);
            foreach($this->search_result as $result)
                foreach($result as $info) {
                    $class = $this->get_class($info,'result');
                    $actions = $this->get_actions($info);
                    $checkbox = $this->get_checkbox($info);
                    $list->add_row($class,$info,$actions,$checkbox);
                }
            $list->render('PLUGIN_PLUGINMANAGER_RENDER_SEARCHRESULT');
        } elseif(!is_null($this->term)) {
            ptln('<h2>'.'The term "'.$this->term.'" was not found'.'</h2>');//TODO Add language
            $url = wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search'));
            ptln('<p>Please try with a simpler query or <a href="'.$url.'" title="'.$url.'" />click here</a> to browse all plugins</p>');
        } else {
            ptln('<h2>'.'Browse all plugins'.'</h2>');//TODO Add language
            $list = new plugins_list($this,'plugins__list',$this->actions_list);
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
                $actions = $this->make_action('download',$info['id'],'Download as disabled');
            } else {
                $actions = $this->make_action('download',$info['id'],$this->lang['btn_download']);
                $actions .= ' | '.$this->make_action('disdown',$info['id'],'Download as disabled');
            }
        } else {
            $actions = "No Download URL";
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
