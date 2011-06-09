<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array();
    var $filter_array = array('id' => NULL,'name' => NULL,'description' => NULL,'tag' => NULL,'type' => NULL, 'author' => NULL);
    var $result = array();
    var $repo = NULL;
    var $extra = NULL;
    var $versions = array();
    
    function process() {
        
        if(array_key_exists('term',$_REQUEST) && @strlen($_REQUEST['term']) > 0)
            $this->term = $_REQUEST['term'];
        if(!is_null($this->term)) {
            if(array_key_exists('filters',$_REQUEST) && is_array($_REQUEST['filters']))
                $this->filters = array_intersect($_REQUEST['filters'],array_keys($this->filter_array));
            else
                $this->filters = array_keys($this->filter_array);
            if(array_key_exists('ext',$_REQUEST) && is_array($_REQUEST['ext']))
                $this->extra = array_intersect_key($_REQUEST['ext'],$this->filter_array);
            if(is_array($this->extra) && array_key_exists('tag',$this->extra))
                $this->extra['tag'] = explode(',',strtolower($this->extra['tag']));
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
        ptln('  <form action="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search')).'" method="post">');
        ptln('    <fieldset class="hidden">',4);
        formSecurityToken();
        ptln('    </fieldset>');
        ptln('    <fieldset>');
        ptln('      <legend>'.$lang['btn_search'].'</legend>');
        ptln('      <label for="dw__search">'.$lang['btn_search'].'<input name="term" id="dw__search" class="edit" type="text" maxlength="200" /></label>');
        ptln('      <label>Type');//TODO Add language
        ptln('        <select name="ext[type]">');
        ptln('          <option value="">All</option>');//TODO Add language
        ptln('          <option value="Syntax">Syntax</option>');//TODO Add language
        ptln('          <option value="Admin">Admin</option>');//TODO Add language
        ptln('          <option value="Action">Action</option>');//TODO Add language
        ptln('          <option value="Renderer">Renderer</option>');//TODO Add language
        ptln('          <option value="Helper">Helper</option>');//TODO Add language
        ptln('          <option value="Template">Template</option>');//TODO Add language
        ptln('        </select>');
        ptln('      </label>');        
        ptln('      <label>Filter by:');//TODO Add language
        ptln('        <select name="filters[]" multiple>');
        ptln('          <option value="id">ID</option>');//TODO Add language
        ptln('          <option value="name">Name</option>');//TODO Add language
        ptln('          <option value="description">Description</option>');//TODO Add language
        ptln('          <option value="tag">Tag</option>');//TODO Add language
        ptln('          <option value="type">Type</option>');//TODO Add language
        ptln('        </select>');
        ptln('      </label>');
        ptln('      <label>Tags: <input name="ext[tag]" class="edit tag" type="text" maxlength="200"/></label>');//TODO Add language
        ptln('      <input type="submit" class="button" name="fn[search]" value="'.$lang['btn_search'].'" />');
        ptln('    </fieldset>');
        ptln('  </form>');
        ptln('</div>');
        ptln('<pre>');
        if(is_array($this->result) && count($this->result))
            print_r($this->result);
        else
            print_r($this->repo);
        ptln('</pre>');
        ptln('</div>');
        //parent::html();
    }

    /**
     * Looks up the term in the repository cache according to filters set. Basic searching.
     * TODO advanced searching options (case-sensitive, for exact term etc) is it necessary??
     */
    protected function lookup() {
        if(!is_null($this->term)) {
            $result = array();
            $tmp=array();
            if(!is_null($this->repo)) {
                foreach($this->filters as $filter) {
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
        if(is_array($this->extra) && count($this->extra))
            foreach($this->extra as $index => $value)
                if(count($value)) {
                    if($index == 'type') {
                        if(!preg_match("/.*$value.*/ism",$plugin['type'])) return true;
                    }
                    elseif($index == 'tag') {
                        foreach($value as $tag)
                            if(strlen($tag))
                                if(@array_search(trim($tag),(array)$plugin['tags']['tag'])===false) return true;
                    }elseif(!(array_key_exists($index,$plugin) && $plugin[$index] == $value)) return true;
                }
        //default case...
        return false;
    }
}
