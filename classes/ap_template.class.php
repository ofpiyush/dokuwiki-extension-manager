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
        $list = $this->manager->template_list;
        $this->templates['enabled'][0] = $this->_info_list($conf['template']);
        if(!empty($_REQUEST['info']))
            $this->showinfo = $_REQUEST['info'];
        if(!empty($this->showinfo) && in_array($this->showinfo,$list)) {
            $list = array_diff($list,array($this->showinfo));
            $infoed = $this->showinfo;
            $this->showinfo = array();
            $this->showinfo['status'] = ($infoed == $conf['template'])? 'enabled' : 'disabled';
            if($this->showinfo['status'] == 'enabled') unset($this->templates['enabled'][0]);
            $this->showinfo['info'] = $this->_info_list($infoed,'template',true);
        } else {
            $this->showinfo = null;
        }
        $disabled = array_diff($list,array($conf['template']));
        $this->templates['disabled'] = array_map(array($this,'_info_list'),$disabled); 
        usort($this->templates['disabled'],array($this,'_sort'));
        $this->actions_list = array(
            'delete'=>$this->get_lang('btn_delete'),
            'update'=>$this->get_lang('btn_update')
        );
    }

    function html() {
        $this->html_menu();
        $this->render_search('tpl__search',$this->get_lang('tpl_search'),'','Template');
        $list = new plugins_list($this,'templates__list',$this->actions_list,'template');
        $list->add_header($this->get_lang('tpl_manage'));
        $list->start_form();
        if(!empty($this->showinfo)) {
            $class = $this->get_class($this->showinfo['info'],'infoed');
            $actions = $this->get_actions($this->showinfo['info'],$this->showinfo['status']);
            $checkbox = $this->get_checkbox($this->showinfo['info']);
            $list->add_row($class,$this->showinfo['info'],$actions,$checkbox);
        }
        if(!empty($this->templates)) {
            
            foreach($this->templates as $type => $templates) {
                foreach ($templates as $template) {
                    $class = $this->get_class($template,$type);
                    $actions = $this->get_actions($template,$type);
                    $checkbox = $this->get_checkbox($template);
                    $list->add_row($class,$template,$actions,$checkbox);
                    if($type == "enabled") $list->rowadded = false;
                }
            }
        }
        $list->end_form();
        $list->render();
    }
    function _info_list($template) {
        return parent::_info_list($template,'template');
    }
    function get_actions(array $info, $type) {
        $extra = array('template'=>'template');
        $actions = $this->make_action('info',$info['id'],$this->get_lang('btn_info'),$extra);
        if($info['id']!="default") {
            $actions .= ' | '.$this->make_action('update',$info['id'],$this->get_lang('btn_update'),$extra);
            if($type == "disabled") {
                $actions .= ' | '.$this->make_action('delete',$info['id'],$this->get_lang('btn_delete'),$extra);
            }
        }
        return $actions;
    }
    function get_checkbox($info) {
        if($info['id'] == 'default') return array('disabled'=>'disabled');
        return array();
    }
    function get_class(array $info,$class) {
        if(!empty($info['securityissue'])) $class .= ' secissue';
        $class .= " template";
        return $class;
    }
}
