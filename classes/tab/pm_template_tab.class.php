<?php
class pm_template_tab extends pm_base_tab {

    var $templates = array();
    var $enabled = array();
    var $tpl_default = array();
    var $actions_list = array();

    function process() {
        global $conf;
        $list = $this->m->template_list;
        $this->templates['enabled'][0] = $this->_info_list($conf['template']);
        $disabled = array_diff($list,array($conf['template']));
        $this->templates['disabled'] = array_map(array($this,'_info_list'),$disabled); 
        usort($this->templates['disabled'],array($this,'_sort'));
        $this->actions_list = array(
            'delete'=>$this->m->getLang('btn_delete'),
            'update'=>$this->m->getLang('btn_update'),
            'reinstall' =>$this->m->getLang('btn_reinstall'),
        );
        $this->possible_errors = array(
            'missing_dependency' => $this->m->getLang('depends'),
            'not_writable' => $this->m->getLang('not_writable'),
            'bundled' => $this->m->getLang('bundled'),
            'missing_dlurl' => $this->m->getLang('no_url'),
        );
    }

    function html() {
        $this->html_menu();
        $this->render_search('tpl__search',$this->m->getLang('tpl_search'),'','Template');
        $list = new pm_plugins_list_lib($this,'templates__list',$this->actions_list,$this->possible_errors,'template');
        $list->add_header($this->m->getLang('tpl_manage'));
        $list->start_form();
        if(!empty($this->templates)) {
            foreach($this->templates as $type => $templates) {
                foreach ($templates as $template) {
                    $class = $this->get_class($template,$type);
                    //$actions = $this->get_actions($template,$type);
                    //$checkbox = $this->get_checkbox($template);
                    $list->add_row($class,$template);//,$actions,$checkbox);
                    if($type == "enabled") $list->rowadded = false;
                }
            }
        }
        $list->end_form(array('update','delete','reinstall'));
        $list->render();
    }
    function check_writable() {
        if(!is_writable(DOKU_INC.'lib/tpl/')) {
            msg($this->m->getLang('not_writable')." ".DOKU_INC.'lib/tpl/',-1);
        }
    }
    function _info_list($template) {
        return parent::_info_list($template,'template');
    }
    function get_class($info,$class) {
        if(!empty($info->securityissue)) $class .= ' secissue';
        if($info->id == $this->m->showinfo) $class .= ' infoed';
        $class .= " template";
        return $class;
    }
}
