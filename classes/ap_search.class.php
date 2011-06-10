<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array();
    var $filter_array = array('id' => NULL,'name' => NULL,'description' => NULL, 'type' => NULL, 'tag' =>NULL, 'author' => NULL);
    var $result = array();
    var $repo = NULL;
    var $extra = NULL;
    var $versions = array();
    
    function process() {
        if(array_key_exists('term',$_REQUEST) && @strlen($_REQUEST['term']) > 0)
            $this->term = $_REQUEST['term'];
        if(!is_null($this->term)) {
            if(array_key_exists('filters',$_REQUEST) && is_array($_REQUEST['filters']))
                $this->filters = array_intersect_key(array_flip($_REQUEST['filters']),$this->filter_array);
            else
                $this->filters = $this->filter_array;
            if(array_key_exists('ext',$_REQUEST) && is_array($_REQUEST['ext']))
                $this->extra = array_intersect_key($_REQUEST['ext'],$this->filter_array);
            if(is_array($this->extra) && array_key_exists('tag',$this->extra))
                $this->extra['tag'] = explode(',',strtolower($this->extra['tag']));
        }
        $this->repo = unserialize($this->repo_cache->retrieveCache());
        if(!is_null($this->term) && !is_null($this->repo))
            $this->lookup();
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
        ptln('          <option value="author">Author</option>');//TODO Add language
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
     * TODO assign weights to matches, like id matches highest in ordering
     */
    protected function lookup() {
        foreach ($this->repo as $single) {
            $matches = array_filter($single,array($this,'search'));
            if(count($matches)) {
                $count = count(array_intersect_key($this->filters,$matches));
                if($count && $this->check($single))
                    $this->result[$count][$single['id']] = $single;
            }
        }
        return $this->result;
    }

    /**
     * Search for the term in every plugin and return matches.
     */
    protected function search($haystack) {
        if(is_array($haystack)) return false;
        return @stripos($haystack,$this->term) !== false;
    }

    /**
     * Checks to figure out if a plugin should be searched, 
     * based on some settings, version, current context(may be?)
     */
    protected function check($plugin) {
        //$version_data = getVersionData();
        //print_r($this->extra);
        if(@$plugin['tags']['tag'][0] == "!bundled") return false;
        if(is_array($this->extra) && count($this->extra))
            foreach($this->extra as $index => $value)
                if(count($value)) {
                    if($index == 'type') {
                        if(strlen($value) && stripos($plugin['type'],$value) === false) return false;
                    }
                    elseif($index == 'tag') {
                        foreach($value as $tag)
                            if(strlen($tag))
                                if(@in_array(trim($tag),(array)$plugin['tags']['tag'])===false) return false;
                    }elseif(!(array_key_exists($index,$plugin) && $plugin[$index] == $value)) return false;
                }
        //All tests passed
        return true;
    }
}
