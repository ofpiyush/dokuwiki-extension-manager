<?php
/**
 * Manage class (Base class with most common functions for more than 1 tabs)
 */

abstract class pm_base_tab {

    var $manager = NULL;
    var $lang = array();
    var $plugin = '';
    var $downloaded = array();

    function __construct(admin_plugin_plugin $manager) {
        $this->manager = $manager;
        $this->plugin = $manager->plugin;
        if(!empty($_REQUEST['info']))
            $this->showinfo = $_REQUEST['info'];
        $this->check_writable();
    }

    abstract function process();

    abstract function html();

    abstract function check_writable();
    // build our standard menu
    function html_menu() {
        global $ID;
            $tabs_array = array(
                'plugin' => rtrim($this->manager->getLang('plugin'),":"),
                'template' =>$this->manager->getLang('template'),
                'search' =>$this->manager->getLang('install')
                );
            $selected = array_key_exists($this->manager->tab,$tabs_array)? $this->manager->tab : 'plugin' ;
            ptln('<div class="pm_menu">');
		    ptln('    <ul>');
		    foreach($tabs_array as $tab =>$text) {
		        // not showing search tab when no repo is present
		        if(empty($this->manager->repo) && $tab == 'search') continue;
		        ptln('	    <li><a class="'.(($tab == $selected)? "selected": "notsel").'" href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>$tab)).'">'.$text.'</a></li>');
		    }
		    ptln('    </ul>');
            ptln('</div>');
    }

    protected function render_search($id,$head,$value = '',$type = null) {
        if($this->manager->tab == 'search' || (empty($this->manager->repo) && $this->manager->tab == 'plugin')) {
            ptln('<div class="common">');
            ptln('  <h2>'.$this->manager->getLang('download').'</h2>');
            $url_form = new Doku_Form('install__url');
            $url_form->startFieldset($this->manager->getLang('download'));
            $url_form->addElement(form_makeTextField('url','',$this->manager->getLang('url'),'dw__url'));
            $url_form->addHidden('page','plugin');
            $url_form->addHidden('fn','download');
            $url_form->addElement(form_makeButton('submit', 'admin', $this->manager->getLang('btn_download') ));
            $url_form->endFieldset();
            $url_form->printForm();
            ptln('</div>');
        }
        // No point producing search when there is no repo
        if(!empty($this->manager->repo)) {
            global $lang;
            ptln('<div class="common">');
            ptln('  <h2>'.hsc($head).'</h2>');
            $search_form = new Doku_Form($id);
            $search_form->startFieldset($lang['btn_search']);
            $search_form->addElement(form_makeTextField('term',hsc($value),$lang['btn_search'],'pm__sfield'));
            $search_form->addHidden('page','plugin');
            $search_form->addHidden('tab','search');
            $search_form->addHidden('fn','search');
            $type_default = "";
            if(!empty($this->extra['type'])) $type_default = $this->extra['type'];
            if($type !== null)
                if(is_array($type) && count($type))
                    $search_form->addElement(form_makeMenuField('type',$type,$type_default,$this->manager->getLang('type')));
                else
                    $search_form->addHidden('type',$type);
            $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
            $search_form->endFieldset();
            $search_form->printForm();
            ptln('</div>');
        }
    }



    function _info_list($index,$type ="plugin") {
        return $this->manager->info->get($index,$type);
    }

    //sorting based on name
    protected function _sort($a,$b) {
        return strnatcasecmp($a->name,$b->name);
    }
}
