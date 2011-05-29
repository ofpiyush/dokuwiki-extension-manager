<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array();
    var $filter_array = array('id','name','description','tag','type','author');
    var $result = array();
    var $repo = NULL;
    var $versions = array();

    function process() {
        if(array_key_exists('term',$_REQUEST) && @strlen($_REQUEST['term']) > 0)
            $this->term = $_REQUEST['term'];
        if(array_key_exists('filters',$_REQUEST) && is_array($_REQUEST['filters']))
            $this->filters = $_REQUEST['filters'];
        if(!is_null($this->term)) {
            $this->repo = unserialize($this->repo_cache->retrieveCache());
            $this->lookup();
        }
    }

    function html() {
        $this->html_menu();
        global $ID,$lang;
        ptln('<div class="pm_info">');
        ptln('<div class="common">');
        ptln('  <h2>'.$this->lang['download'].'</h2>');
        ptln('  <form action="'.wl($ID,array('do'=>'admin','page'=>'plugin')).'" method="post">');
        ptln('    <fieldset class="hidden">',4);
        formSecurityToken();
        ptln('    </fieldset>');
        ptln('    <fieldset>');
        ptln('      <legend>'.$this->lang['download'].'</legend>');
        ptln('      <label for="dw__url">'.$this->lang['url'].'<input name="url" id="dw__url" class="edit" type="text" maxlength="200" /></label>');
        ptln('      <input type="submit" class="button" name="fn[download]" value="'.$this->lang['btn_download'].'" />');
        ptln('    </fieldset>');
        ptln('  </form>');
        ptln('</div>');
        if(is_array($this->result) && count($this->result)) {
            ptln('<pre>');
            print_r($this->result);
            ptln('</pre>');
        }
        ptln('</div>');
        //parent::html();
    }

    /**
     * Looks up the term in the repository cache according to filters set. Basic searching.
     * TODO advanced searching options (case-sensitive, for exact term etc) is it necessary??
     */
    protected function lookup() {
        if(!is_null($this->term)) {
            if(is_array($this->filters) && count($this->filters))
                $filters =array_intersect($this->filters,$this->filter_array);
            else
                $filters = $this->filter_array;
            $result = array();
            $tmp=array();
            if(!is_null($this->repo)) {
                foreach($filters as $filter) {
                    foreach ($this->repo as $single) {
                        if($this->check($single)) continue;
                        if($filter == 'tag') {
                            if(is_array($single['tags'])) {
                                foreach((array)$single['tags']['tag'] as $tag)
                                    if(preg_match("/.*$this->term.*/ism",$tag))
                                        $tmp[$single['id']] = $single;
                            }
                        } elseif(preg_match("/.*$this->term.*/ism",$single[$filter]))
                            $tmp[$single['id']] = $single;
                        $intersect = array_intersect_key($result,$tmp);
                        $result = array_diff_key($tmp, $result);
                        $result = array_merge($intersect,$result);
                    }
                }
                return $this->result = $result;
            }
        }
    }

    /**
     * Checks to figure out if a plugin should be searched, 
     * based on some settings, version, current context(may be?)
     */
    protected function check(array $plugin) {
        $version_data = getVersionData();
        if(@$plugin['tags']['tag'][0] == "!bundled") return true;
        //default case...
        return false;
    }
}
