<?php
/**
 * Search tab render class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_search_tab extends pm_base_tab {

    var $query = NULL;
    var $term = NULL;
    var $extra = NULL;
    var $filters = array();
    var $search_result = array();
    var $filtered_repo = NULL;
    var $actions_list = array();

    function process() {
        $this->clean_repo();
        $this->actions_list = array(
            'enable'=>$this->manager->getLang('enable'),
            'disable'=>$this->manager->getLang('btn_disable'),
            'update'=>$this->manager->getLang('btn_update'),
            'reinstall' =>$this->manager->getLang('btn_reinstall'),
            'download'=>$this->manager->getLang('btn_download'),
            'download_disabled'=>$this->manager->getLang('btn_disdown'),
            'download_dependency' => $this->manager->getLang('btn_dependown'),
            );
        $this->possible_errors = array(
            'bundled' => $this->manager->getLang('bundled_source'),
            'has_conflicts' => $this->manager->getLang('conflicts'),
            'missing_dependency' => $this->manager->getLang('depends'),
            'gitmanaged' => $this->manager->getLang('gitmanaged'),
            'missing_dlurl' => $this->manager->getLang('no_url'),
            'not_writable' => $this->manager->getLang('not_writable'),
            );

        // list of properties that are included in the search
        $this->filters = array('id' => NULL,'name' => NULL,'description' => NULL, 'type' => NULL, 'tag' =>NULL, 'author' => NULL);

        if(!empty($_REQUEST['q'])) {
            $this->query = $_REQUEST['q'];
        }
        if(!empty($_REQUEST['type'])) {
            $this->query .= ' @'.$_REQUEST['type'];
        }

        if (preg_match_all('/(@plugins*|@templates*|\w+\s*:\s*\w+|".*?"|\w+)+/i',$this->query,$matches)) {
            foreach ($matches[0] as $match) {
                if (stripos($match,'@plugin') !== false) {
                    $this->extra['is_template'] = false;

                } elseif (stripos($match,'@template') !== false) {
                    $this->extra['is_template'] = true;

                } elseif (strpos($match,':') !== false) {
                    list($key,$val) = explode(':', $match,2);
                    $this->extra[strtolower(trim($key))] = trim($val);

                } elseif (strpos($match,'"') !== false) {
                    $this->term[] = str_replace('"','',$match);

                } else {
                    $this->term[] = $match;
                }
            }
        }

        if($this->term !== null || $this->extra !== null ) {
            if($this->term === null) $this->term = array();
            if($this->manager->repo)
                $this->lookup();
        }
    }

    /**
     * Search tab rendering
     */
    function html() {
        $this->html_menu();
        ptln('<div class="panelHeader">');
        if ($this->manager->repo) {
            $summary = sprintf($this->manager->getLang('summary_search'),count($this->manager->repo['data']));
            ptln('<h3>'.$summary.'</h3>');
        } else {
            echo '<div class="message error">'.$this->manager->getLang('repocache_error').'</div>';
        }
        $this->reload_repo_link();
        $this->html_download_disabled();
        ptln('</div><!-- panelHeader -->');

        ptln('<div class="tagcloud">');
        $this->tagcloud();
        ptln('</div>');
        ptln('<div class="search">');
        $this->html_search(null,$this->query);
        $this->html_urldownload();
        ptln('</div>');

        ptln('<div class="panelContent">');
        $this->html_extensionlist();
        ptln('</div><!-- panelContent -->');
    }

    function html_extensionlist() {
        if(is_array($this->search_result) && count($this->search_result)) {
            $type = (!empty($this->extra['type']) && $this->extra['type'] == "Template" )? 'template': 'plugin' ;
            $list = new pm_plugins_list_lib($this->manager,'extensionplugin__searchresult',$this->actions_list,$this->possible_errors,$type);
            $list->add_header('search_results',sprintf($this->manager->getLang('header_search_results'),hsc($this->query)));
            $list->start_form();
            foreach($this->search_result as $result) {
                foreach($result as $info) {
                    $info = $this->_info_list($info['id']);
                    $list->add_row($info);
                }
            }
            $list->end_form(array_keys($this->actions_list));
            $list->render();

        } elseif(!is_null($this->query)) {
            $no_result = new pm_plugins_list_lib($this->manager,'extensionplugin__noresult');
            $no_result->add_header('search_results',sprintf($this->manager->getLang('not_found'),hsc($this->query)));
            $url = wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search'));
            $no_result->add_p(sprintf($this->manager->getLang('no_result'),$url,$url));
            $no_result->render();

        } else {
            $full_list = new pm_plugins_list_lib($this->manager,'extensionplugin__browselist',$this->actions_list,$this->possible_errors);
            $full_list->add_header('search_results',$this->manager->getLang('browse'));
            $full_list->start_form();
            foreach($this->filtered_repo as $info) {
                $info = $this->_info_list($info['id']);
                $full_list->add_row($info);
            }
            $full_list->end_form(array_keys($this->actions_list));
            $full_list->render();
        }
    }

    function check_writable() {
        if(!$this->manager->templatefolder_writable) {
            msg($this->manager->getLang('not_writable')." ".DOKU_TPLLIB,-1);
        }
        if(!$this->manager->pluginfolder_writable) {
            msg($this->manager->getLang('not_writable')." ".DOKU_PLUGIN,-1);
        }
    }

    /**
     * Output plugin tag filter selection (cloud)
     */
    function tagcloud(){
        global $ID;

        $tags = $this->manager->repo['cloud'];
        if (count($tags) > 0) {
            echo '<div class="cloud">'.NL;
            foreach($tags as $tag => $size){
                echo $this->html_taglink($tag, 'cl'.$size);
            }
            echo '</div>'.NL;
        }
    }

    /**
     * Filter BEFORE the repo is searched on, removes obsolete plugins, security issues etc
     */
    protected function clean_repo() {
        if ($this->manager->repo) {
            $this->filtered_repo = array_filter($this->manager->repo['data'],create_function('$info','return $info["show"];'));
            $this->filtered_repo = array_merge($this->filtered_repo, $this->local_extensions());
        } else {
            $this->filtered_repo = $this->local_extensions();
        }
        uasort($this->filtered_repo, function($a,$b){return strcasecmp($a['sort'],$b['sort']);});
    }

    /**
     * Create dummy repo entries for local extensions
     */
    function local_extensions() {
        $retval = array();
        $templates = array_map(array($this,'_info_templatelist') ,$this->manager->template_list);
        $plugins = array_map(array($this,'_info_pluginlist'),$this->manager->plugin_list);
        $list = array_merge($plugins,$templates);
        foreach ($list as $info) {
            if ($info->repo) {
                // only use repo if we are sure that this plugin is connected to repo
                $retval[$info->repokey] = $info->repo;
                $retval[$info->repokey]['id'] = $info->cmdkey;
            } else {
                $retval['L'.$info->repokey] = array('id' => $info->cmdkey,
                                                'name' => $info->name,
                                                'author' => $info->author,
                                                'description' => $info->desc,
                                                'sort' => str_replace('template:','',$info->repokey)
                                                );
            }
        }
        return $retval;
    }

    function _info_pluginlist($index) {
        return $this->manager->info->get($index,'plugin');
    }

    function _info_templatelist($index) {
        return $this->manager->info->get($index,'template');
    }

    /**
     * Looks up the term in the repository cache according to filters set
     */
    protected function lookup() {
        foreach ($this->filtered_repo as $single) {
            if (!$this->check($single)) continue;
            // search
            $matches = array_filter($single,array($this,'search'));
            if(count($matches)) {
                $weight = count(array_intersect_key($this->filters,$matches));
                if($weight) {
                // TODO
                    // increase weight for id (repokey) match
                    // if (stripos($single['id'],$this->term)!==false) {
                        // $weight += 8;
                        // if ($single['id'] == $this->term) $weight += 8;
                    // }
                    // increase weight for name match
                    // if(stripos($single['name'],$this->term)!==false) {
                        // $weight += 6;
                        // if ($single['name'] == $this->term) $weight += 6;
                    // }
                    $this->search_result[$weight][$single['id']] = $single;
                }
            }
        }
        return krsort($this->search_result);
    }

    /**
     * Search for the term in every plugin and return matches
     */
    protected function search($haystack) {
        if(is_array($haystack)) {
            return (bool) count(array_filter((array)$haystack,array($this,'search')));
        }
        foreach ($this->term as $t) {
            if (@stripos($haystack,$t) === false) return false;
        }
        return true;
    }

    /**
     * Checks to figure out if a plugin should be searched, all $extra[] conditions must match
     */
    protected function check($plugin) {
        if(is_array($this->extra)) {
            foreach($this->extra as $key => $value) {
                if ($key == 'is_template') {
                    if ($value) {
                        if (strpos($plugin['id'],'template:') === false) return false;
                    } else {
                        if (strpos($plugin['id'],'template:') !== false) return false;
                    }

                } elseif ($key == 'tag') {
                    if(@in_array($value,(array)$plugin['tags']['tag']) === false) return false;

                } elseif (!array_key_exists($key,$plugin) || stripos($plugin[$key], $value) === false) {
                    return false;
                }
            }
        }
        return true;
    }

}
