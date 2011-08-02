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
        $this->repo = $this->fetch_cache();
        if(!is_null($this->term) && !is_null($this->repo))
            $this->lookup();
    }

    function html() {
        $this->html_menu();
        global $lang;
        ptln('<div class="pm_info">');
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
        $search_form = new Doku_Form('install__search');
        $search_form->startFieldset($lang['btn_search']);
        $search_form->addElement(form_makeTextField('term','',$lang['btn_search'],'dw__search'));
        $search_form->addElement(form_makeMenuField('ext[type]',array(
                                                                ''=>'All',//TODO add language
                                                                'Syntax'=>'Syntax',//TODO add language
                                                                'Admin'=>'Admin',//TODO add language
                                                                'Action'=>'Action',//TODO add language
                                                                'Renderer'=>'Renderer',//TODO add language
                                                                'Helper'=>'Helper',//TODO add language
                                                                'Template'=>'Template')//TODO add language
                                                                ,'','Type'));//TODO add language
        $search_form->addElement(form_makeListboxField('filters[]',array(
                                                                'id'=>'ID',//TODO add language
                                                                'name'=>'Name',//TODO add language
                                                                'description'=>'Description',//TODO add language
                                                                'author'=>'Author',//TODO add language
                                                                'tag'=>'Tag',//TODO add language
                                                                'type'=>'Type')//TODO add language
                                                                ,'','Filter by:','','',array('multiple'=>'multiple')));//TODO add language
        $search_form->addHidden('page','plugin');
        $search_form->addHidden('tab','search');
        $search_form->addHidden('fn[search]',$lang['btn_search']);
        $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
        $search_form->endFieldset();
        $search_form->printForm();
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
