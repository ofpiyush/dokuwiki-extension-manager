<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array('id' => NULL,'name' => NULL,'description' => NULL, 'type' => NULL, 'tag' =>NULL, 'author' => NULL);
    var $result = array();
    var $repo = NULL;
    var $filtered_repo = NULL;
    var $extra = NULL;
    var $versions = array();
    
    function process() {
        $this->clean_repo();
        if(array_key_exists('term',$_REQUEST) && @strlen($_REQUEST['term']) > 0) {
            $this->term = $_REQUEST['term'];
            //add parsing for key=value based extras
        }
        if($this->term !== null) {
            if(array_key_exists('type',$_REQUEST) && !empty($_REQUEST['type'])) {
                $this->extra['type'] = $_REQUEST['type'];
            }
            if($this->repo !== null)
                $this->lookup();
        }
    }

    function html() {
        $this->html_menu();
        global $lang;
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
        $search_form->addElement(form_makeMenuField('type',array(
                                                                ''=>'-Please Choose-',//TODO add language
                                                                false=>'All',//TODO add language
                                                                'Syntax'=>'Syntax',//TODO add language
                                                                'Admin'=>'Admin',//TODO add language
                                                                'Action'=>'Action',//TODO add language
                                                                'Renderer'=>'Renderer',//TODO add language
                                                                'Helper'=>'Helper',//TODO add language
                                                                'Template'=>'Template')//TODO add language
                                                                ,'','','','',array('class'=>'quickselect')));//TODO add language
        $search_form->addHidden('page','plugin');
        $search_form->addHidden('tab','search');
        $search_form->addHidden('fn','search');
        $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
        $search_form->endFieldset();
        $search_form->printForm();
        ptln('</div>');
        if(is_array($this->result) && count($this->result)) {
            ptln('<h2>'.'Search results for "'.$this->term.'"</h2>');//TODO Add language
            $form = new Doku_Form("search__result");
            $form->addHidden('page','plugin');
            $form->addHidden('fn','multiselect');
            $form->addElement(form_makeOpenTag('table',array('class'=>'inline')));
            foreach($this->result as $result)
                foreach($result as $info) {
                    $class = 'result';
                    if((@array_key_exists('securityissue',$info) && !empty($info['securityissue'])) )
                        $class .= ' secissue';
                    $this->make_form($form,$info,$class);
                }
            $form->addElement(form_makeCloseTag('table'));
            $form->addHidden('action','download');
            $form->addElement(form_makeButton('submit', 'admin', 'Download' ));
            html_form('SEARCH_RESULT',$form);
        } elseif(!is_null($this->term)) {
                ptln('<h2>'.'The term "'.$this->term.'" was not found</h2>');//TODO Add language
                $url = wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search'));
                ptln('<p>Please try with a simpler query or <a href="'.$url.'" title="'.$url.'" />click here</a> to browse all plugins</p>');
        } else {
            ptln('<h2>'.'Browse plugins'.'</h2>');//TODO Add language
            $form = new Doku_Form("plugins__list");
            $form->addHidden('page','plugin');
            $form->addHidden('fn','multiselect');
            $form->addElement(form_makeOpenTag('table',array('class'=>'inline')));
            foreach($this->filtered_repo as $info) {
                $class = 'all';
                if((@array_key_exists('securityissue',$info) && !empty($info['securityissue'])) )
                    $class .= ' secissue';
                $this->make_form($form,$info,$class);
            }
            $form->addElement(form_makeCloseTag('table'));
            $form->addHidden('action','download');
            $form->addElement(form_makeButton('submit', 'admin', 'Download' ));
            html_form('PLUGIN_LIST',$form);
        }

        //parent::html();
    }
    protected function make_form($form,$info,$class) {
        $form->addElement(form_makeOpenTag('tr',array('class'=>$class)));
        $form->addElement(form_makeOpenTag('td',array('class'=>'checkbox')));
        $form->addElement(form_makeCheckboxField('checked[]',$info['downloadurl'],'',''));
        $form->addElement(form_makeCloseTag('td'));
        $form->addElement(form_makeOpenTag('td',array('class'=>'legend')));
        $form->addElement(form_makeOpenTag('span',array('class'=>'head')));
        $form->addElement($this->make_title($info));
        $form->addElement(form_makeCloseTag('span'));
        if(isset($info['description'])) {
            $form->addElement(form_makeOpenTag('p'));
            $form->addElement(hsc($info['description']));
            $form->addElement(form_makeCloseTag('p'));
        }
        $form->addElement(form_makeCloseTag('td'));
        $form->addElement(form_makeOpenTag('td',array('class'=>'actions')));
        $form->addElement(form_makeOpenTag('p'));
        if(isset($info['downloadurl']) && !empty($info['downloadurl']))
            $form->addElement('<a href="'.$this->make_url('download',$info['downloadurl']).'">Download</a> ');
        else
            $form->addElement('No Download URL');
        $form->addElement(form_makeCloseTag('p'));
        $form->addElement(form_makeCloseTag('td'));
        $form->addElement(form_makeCloseTag('tr'));
    }

    protected function clean_repo() {
        $this->filtered_repo = array_diff_key($this->repo,array_flip($this->_bundled));
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
                if($count && $this->check($single))
                    $this->result[$count][$single['id']] = $single;
            }
        }
        return krsort($this->result);
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
