<?php
class ap_template extends plugins_base {

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
    function get_actions($info, $type) {
        $extra = array('template'=>'template');
        $actions = $this->make_action('info',$info->id,$this->get_lang('btn_info'),$extra);
        if($info->id!="default") {
            $actions .= ' | '.$this->make_action('update',$info->id,$this->get_lang('btn_update'),$extra);
            if($type == "disabled") {
                $actions .= ' | '.$this->make_action('delete',$info->id,$this->get_lang('btn_delete'),$extra);
            }
        }
        return $actions;
    }
    function get_checkbox($info) {
        if($info->id == 'default') return array('disabled'=>'disabled');
        return array();
    }
    function get_class($info,$class) {
        if(!empty($info->securityissue)) $class .= ' secissue';
        if($info->id == $this->showinfo) $class .= ' infoed';
        $class .= " template";
        return $class;
    }
}
