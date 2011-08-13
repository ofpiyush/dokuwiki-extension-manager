<?php
class pm_plugin_tab extends pm_base_tab {
    var $plugins;
    var $protected_plugins;
    var $actions_list;
    var $showinfo;

    function process() {
        global $plugin_protected;
        $this->actions_list = array(
            'enable'=>$this->m->getLang('enable'),
            'disable'=>$this->m->getLang('btn_disable'),
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
        $list = array_map(array($this,'_info_list'),$this->m->plugin_list);
        usort($list,array($this,'_sort'));
        $protected = array_filter($list,array($this,'_is_protected'));
        $notprotected = array_diff_key($list,$protected);
        $this->plugins['enabled'] = array_filter($notprotected,array($this,'_is_enabled'));
        $this->plugins['disabled'] = array_diff_key($notprotected,$this->plugins['enabled']);
        $this->protected_plugins['enabled'] = array_filter($protected,array($this,'_is_enabled'));
        $this->protected_plugins['disabled'] = array_diff_key($protected,$this->protected_plugins['enabled']);
    }

    function _is_protected($info) {
        return $info->is_protected;
    }
    function _is_enabled($info) {
        return $info->is_enabled;
    }
    function html() {
        global $lang;
        $this->html_menu();
        print $this->m->locale_xhtml('admin_plugin');
        $this->render_search('pm__search',$this->m->getLang('search_plugin'));
        /**
         * List plugins
         */
        if(is_array($this->plugins) && count($this->plugins)) {
            $list = new pm_plugins_list_lib($this,'plugins__list',$this->actions_list,$this->possible_errors);
            $list->add_header($this->m->getLang('manage'));
            $list->start_form();
            foreach($this->plugins as $type => $plugins) {
                foreach($plugins as $info) {
                    $class = $this->get_class($info,$type);
                    //$actions = $this->get_actions($info,$type);
                    $list->add_row($class,$info,$actions);
                }
            }
            $list->end_form(array('enable','disable','reinstall','delete','update'));
            $list->render();
        }
        if(is_array($this->protected_plugins) && count($this->protected_plugins)) {
            $protected_list = new pm_plugins_list_lib($this,'plugins__protected',array(),$this->possible_errors);
            $protected_list->add_header($this->m->getLang('protected_head'));
            $protected_list->add_p($this->m->getLang('protected_desc'));  
            $protected_list->start_form();
            foreach($this->protected_plugins as $type => $plugins)
                foreach( $plugins as  $info) {
                    $class = $this->get_class($info,"protected ".$type);
                    $protected_list->add_row($class,$info);
            }
            $protected_list->end_form(array());
            $protected_list->render();
        }
        //end list plugins
    }

    function check_writable() {
        if(!is_writable(DOKU_PLUGIN)) {
            msg($this->m->getLang('not_writable')." ".DOKU_PLUGIN,-1);
        }
    }
    function get_class( $info,$class) {
        if(!empty($info->securityissue)) $class .= ' secissue';
        if($info->id === $this->m->showinfo) $class .= " infoed";
        return $class;
    }
}
