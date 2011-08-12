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
        $this->m = $manager;
        $this->plugin = $manager->plugin;
        if(!empty($_REQUEST['info']))
            $this->showinfo = $_REQUEST['info'];
    }

    abstract function process();

    abstract function html();

    abstract function get_actions( $info, $type);

    abstract function get_class( $info, $class);
    abstract function get_checkbox($input);
    // build our standard menu
    function html_menu() {
        global $ID;
            $tabs_array = array(
                'plugin' => rtrim($this->m->getLang('plugin'),":"),
                'template' =>$this->m->getLang('template'),
                'search' =>$this->m->getLang('install')
                );
            $selected = array_key_exists($this->m->tab,$tabs_array)? $this->m->tab : 'plugin' ;
            ptln('<div class="pm_menu">');
		    ptln('    <ul>');
		    foreach($tabs_array as $tab =>$text) {
		        // not showing search tab when no repo is present
		        if(empty($this->m->repo) && $tab == 'search') continue;
		        ptln('	    <li><a class="'.(($tab == $selected)? "selected": "notsel").'" href="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>$tab)).'">'.$text.'</a></li>');
		    }
		    ptln('    </ul>');
            ptln('</div>');
    }

    protected function render_search($id,$head,$value = '',$type = null) {
        if($this->m->tab == 'search' || (empty($this->m->repo) && $this->m->tab == 'plugin')) {
            ptln('<div class="common">');
            ptln('  <h2>'.$this->m->getLang('download').'</h2>');
            $url_form = new Doku_Form('install__url');
            $url_form->startFieldset($this->m->getLang('download'));
            $url_form->addElement(form_makeTextField('url','',$this->m->getLang('url'),'dw__url'));
            $url_form->addHidden('page','plugin');
            $url_form->addHidden('fn','download');
            $url_form->addElement(form_makeButton('submit', 'admin', $this->m->getLang('btn_download') ));
            $url_form->endFieldset();
            $url_form->printForm();
            ptln('</div>');
        }
        // No point producing search when there is no repo
        if(!empty($this->m->repo)) {
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
                    $search_form->addElement(form_makeMenuField('type',$type,$type_default,''));
                else
                    $search_form->addHidden('type',$type);
            $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
            $search_form->endFieldset();
            $search_form->printForm();
            ptln('</div>');
        }
    }

    function make_action($action,$plugin,$value,$extra = false) {
        global $ID;
        $params = array(
            'do'=>'admin',
            'page'=>'plugin',
            'fn'=>$action,
            'checked[]'=>$plugin,
            'sectok'=>getSecurityToken()
        );
        if(!empty($extra)) $params = array_merge($params,$extra);
        $url = wl($ID,$params);
        return '<a href="'.$url.'" class="'.$action.'" title="'.$url.'">'.hsc($value).'</a>';
    }

    function _info_list($index,$type ="plugin") {
        return $this->m->info->get($index,$type);
    }

    //sorting based on name
    protected function _sort($a,$b) {
        return strnatcasecmp($a->name,$b->name);
    }
}
