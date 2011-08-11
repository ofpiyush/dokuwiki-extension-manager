<?php
class ap_template extends ap_manage {

    var $info_list_type = "template";
    var $info_list_path = NULL;
    var $templates = array();
    var $enabled = array();
    var $tpl_default = array();
    var $actions_list = array();

    function process() {
        global $conf;
        $this->info_list_path = DOKU_INC.'lib/tpl/';
        $this->enabled = $this->_info_list($conf['template']);
        if($conf['template']!='default')
            $this->tpl_default = $this->_info_list('default');
        $list = array_diff($this->manager->template_list,array($conf['template'],'default'));
        $this->templates = array_map(array($this,'_info_list'),$list); 
        usort($this->templates,array($this,'_sort'));
        $this->actions_list = array(
            'delete'=>'Delete',//TODO add language
            'update'=>'Update'//TODO add language
        );
    }

    function html() {
        $this->html_menu();
        $this->render_search('tpl__search','Search for a new Template','','Template');
        ptln('<h2>'.'Installed Templates'.'</h2>');
        $list = new plugins_list($this,'templates_list',$this->actions_list,'template');
        $extra_actions = array('template'=>'template');
        // work on the view for enabled template
        //$list->enabled_tpl_row($this->enabled,$this->make_action('update',$this->enabled['id'],'Update',true));
        $actions = "";
        if($this->enabled['id'] !='default')
            $actions .=$this->make_action('update',$this->enabled['id'],'Update',$extra_actions);
        $list->add_row("template enabled",$this->enabled,$actions);
        if(!empty($this->templates)) {
            $class = 'template disabled';            
            foreach($this->templates as $template) {
                $actions = $this->make_action('update',$template['id'],'Update',$extra_actions);
                $actions .= ' | '.$this->make_action('delete',$template['id'],'Delete',$extra_actions);
                $list->add_row($class,$template,$actions);
            }
        }
        if(!empty($this->tpl_default)) {
            $list->add_row($class,$this->tpl_default,'');
        }
        $list->render('PLUGIN_PLUGINMANAGER_RENDER_PLUGINSLIST');
    }
    function _info_list($template) {
        return parent::_info_list($template,'template');
    }
}
