<?php
/**
 * Search tab render class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
class pm_search_tab extends pm_base_tab {

    var $query = null;
    var $term = null;
    var $extra = null;
    var $filters = array();
    var $search_result = array();
    var $search_result_type = 'template';
    var $filtered_repo = null;
    var $actions_list = array();

    function process() {
        global $INPUT;

        $this->filtered_repo   = $this->helper->get_filtered_repo();
        $this->actions_list    = array(
            'enable'              => $this->manager->getLang('btn_enable'),
            'disable'             => $this->manager->getLang('btn_disable'),
            'update'              => $this->manager->getLang('btn_update'),
            'reinstall'           => $this->manager->getLang('btn_reinstall'),
            'download'            => $this->manager->getLang('btn_download'),
            'download_dependency' => $this->manager->getLang('btn_dependown'),
        );
        $this->possible_errors = array(
            'needed_by'     => $this->manager->getLang('needed_by'),
            'bundled'       => $this->manager->getLang('bundled_source'),
            'has_conflicts' => $this->manager->getLang('conflicts'),
            'gitmanaged'    => $this->manager->getLang('gitmanaged'),
            'missing_dlurl' => $this->manager->getLang('no_url'),
            'not_writable'  => $this->manager->getLang('not_writable'),
        );

        // list of properties that are included in the search
        $this->filters = array('id' => null, 'name' => null, 'description' => null, 'type' => null, 'tag' => null, 'author' => null, 'lastupdate' => null);

        $this->query = $INPUT->str('q', null);
        if($INPUT->has('type')) {
            $this->query .= ' @'.$INPUT->str('type');
        }

        $tmpQuery = 'lastupdate:'.date('Y-m-d', time()-60*60*24*30);
        if($this->query) $tmpQuery = $this->query;

        if(preg_match_all('/(@plugins*|@templates*|\w+\s*:\s*[\w\-]+|".*?"|\w+)+/i', $tmpQuery, $matches)) {
            foreach($matches[0] as $match) {
                if(stripos($match, '@plugin') !== false) {
                    $this->extra['is_template'] = false;

                } elseif(stripos($match, '@template') !== false) {
                    $this->extra['is_template'] = true;

                } elseif(strpos($match, ':') !== false) {
                    list($key, $val) = explode(':', $match, 2);
                    $this->extra[strtolower(trim($key))] = trim($val);

                } elseif(strpos($match, '"') !== false) {
                    $this->term[] = str_replace('"', '', $match);

                } else {
                    $this->term[] = $match;
                }
            }
        }

        if($this->term !== null || $this->extra !== null) {
            if($this->term === null) $this->term = array();
            if($this->helper->repo)
                $this->lookup();
        }
    }

    /**
     * Search tab rendering
     */
    function html() {
        $this->html_menu();
        ptln('<div class="panelHeader">');
        if($this->helper->repo) {
            $summary = sprintf($this->manager->getLang('summary_search'), count($this->helper->repo['data']));
            ptln('<p>'.$summary.'</p>');
        } else {
            echo '<div class="msg error">'.$this->manager->getLang('error_repoempty').'</div>';
        }
        $this->reload_repo_link();
        $this->html_download_disabled_msg();
        ptln('<div class="clearer"></div></div><!-- panelHeader -->');

        ptln('<div class="tagcloud">');
        $this->tagcloud();
        ptln('</div>');
        ptln('<div class="search">');
        $this->html_search(null, $this->query);
        $this->html_urldownload();
        ptln('</div>');

        ptln('<div class="panelContent">');
        $this->html_extensionlist();
        ptln('</div><!-- panelContent -->');
    }

    function html_extensionlist() {
        global $INPUT;
        if(!is_null($this->query) && is_array($this->search_result) && count($this->search_result)) {

            if($this->search_result['installed']) {
                $list = new pm_plugins_list_lib($this->manager, 'extensionplugin__searchinstalled', $this->actions_list, $this->possible_errors, $this->search_result_type);
                $list->add_header('installed_extensions', $this->manager->getLang('header_searchinstalled'));
                $list->start_form();
                foreach($this->search_result['installed'] as $result) {
                    foreach($result as $info) {
                        $info = $this->_info_list($info['id']);
                        $list->add_row($info);
                    }
                }
                $list->end_form();
                $list->render();
            }

            if($this->search_result['repo']) {
                $list = new pm_plugins_list_lib($this->manager, 'extensionplugin__searchresult', $this->actions_list, $this->possible_errors, $this->search_result_type);
                $list->add_header('search_results', sprintf($this->manager->getLang('header_search_results'), hsc($this->query)));
                $list->start_form();
                foreach($this->search_result['repo'] as $result) {
                    foreach($result as $info) {
                        $info = $this->_info_list($info['id']);
                        $list->add_row($info);
                    }
                }
                $list->end_form(array_keys($this->actions_list));
                $list->render();
            }

        } elseif(!is_null($this->query)) {
            $no_result = new pm_plugins_list_lib($this->manager, 'extensionplugin__noresult');
            $no_result->add_header('search_results', sprintf($this->manager->getLang('not_found'), hsc($this->query)));
            $url = wl($ID, array('do' => 'admin', 'page' => 'extension', 'tab' => 'search', 'browseall' => 'true'));
            $no_result->add_p(sprintf($this->manager->getLang('no_result'), $url, $url));
            $no_result->render();

        } elseif($INPUT->has('browseall')) {
            $full_list = new pm_plugins_list_lib($this->manager, 'extensionplugin__browselist', $this->actions_list, $this->possible_errors);
            $full_list->add_header('search_results', $this->manager->getLang('browse'));
            $full_list->start_form();
            foreach($this->filtered_repo as $info) {
                $info = $this->_info_list($info['id']);
                $full_list->add_row($info);
            }
            $full_list->end_form(array_keys($this->actions_list));
            $full_list->render();

        } else {
            $list = new pm_plugins_list_lib($this->manager, 'extensionplugin__searchresult', $this->actions_list, $this->possible_errors, $this->search_result_type);
            $list->add_header('search_results', $this->manager->getLang('header_recentlyupdated'));
            $list->start_form();
            if($this->search_result['repo']) {
                foreach($this->search_result['repo'] as $result) {
                    foreach($result as $info) {
                        $info = $this->_info_list($info['id']);
                        $list->add_row($info);
                    }
                }
            }
            $list->end_form(array_keys($this->actions_list));
            $list->render();
        }
    }

    function check_writable() {
        if(!$this->helper->templatefolder_writable) {
            msg($this->manager->getLang('not_writable')." ".DOKU_TPLLIB, -1);
        }
        if(!$this->helper->pluginfolder_writable) {
            msg($this->manager->getLang('not_writable')." ".DOKU_PLUGIN, -1);
        }
    }

    /**
     * Output plugin tag filter selection (cloud)
     */
    function tagcloud() {
        global $ID;

        $tags = $this->helper->repo['cloud'];
        if(count($tags) > 0) {
            echo '<div class="cloud">'.NL;
            foreach($tags as $tag => $size) {
                echo $this->html_taglink($tag, 'cl'.$size);
            }
            echo '</div>'.NL;
        }
    }

    /**
     * Looks up the term in the repository cache according to filters set
     */
    protected function lookup() {
        foreach($this->filtered_repo as $single) {
            if(!$this->check($single)) continue;
            // search
            $matches = array_filter($single, array($this, 'search'));
            if(count($matches)) {
                $weight = count(array_intersect_key($this->filters, $matches));
                if($weight) {
                    // increase weight for id (repokey) match
                    foreach($this->term as $term) {
                        if(stripos($single['id'], $term) !== false) {
                            $weight += 5;
                            if($single['id'] == $term) $weight += 10;
                        }
                    }

                    $group                                               = (strpos($single['id'], '/') !== false ? 'installed' : 'repo');
                    $this->search_result[$group][$weight][$single['id']] = $single;
                    if(substr($single['id'], 0, 9) != 'template:') $this->search_result_type = 'plugin';
                }
            }
        }
        foreach($this->search_result as &$group) {
            krsort($group);
        }
        return;
    }

    /**
     * Search for the term in every plugin and return matches
     */
    protected function search($haystack) {
        if(is_array($haystack)) {
            return (bool) count(array_filter((array) $haystack, array($this, 'search')));
        }
        foreach($this->term as $t) {
            if(@stripos($haystack, $t) === false) return false;
        }
        return true;
    }

    /**
     * Checks to figure out if a plugin should be searched, all $extra[] conditions must match
     */
    protected function check($plugin) {
        if(is_array($this->extra)) {
            foreach($this->extra as $key => $value) {
                if($key == 'is_template') {
                    if($value) {
                        if(strpos($plugin['id'], 'template:') === false) return false;
                    } else {
                        if(strpos($plugin['id'], 'template:') !== false) return false;
                    }

                } elseif($key == 'tag') {
                    if(@in_array($value, (array) $plugin['tags']['tag']) === false) return false;

                } elseif($key == 'lastupdate') {
                    if(!$plugin[$key] || strtotime($plugin[$key]) < strtotime($value)) return false;

                } elseif(!array_key_exists($key, $plugin) || stripos($plugin[$key], $value) === false) {
                    return false;
                }
            }
        }
        return true;
    }

}
