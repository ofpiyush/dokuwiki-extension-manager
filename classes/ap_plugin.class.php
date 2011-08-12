<?php
class ap_plugin extends plugins_base {
    var $plugins;
    var $protected_plugins;
    var $actions_list;
    var $showinfo;

    function process() {
        global $plugin_protected;
        $this->actions_list = array(
            'enable'=>$this->get_lang('enable'),
            'disable'=>$this->get_lang('btn_disable'),
            'delete'=>$this->get_lang('btn_delete'),
            'update'=>$this->get_lang('btn_update'),
        );
        $list = $this->manager->plugin_list;
        if(!empty($_REQUEST['info']) && in_array($_REQUEST['info'],$list))
            $this->showinfo = $_REQUEST['info'];
        $unprotected = array_diff($list,$plugin_protected);
        $enabled = array_intersect($unprotected,plugin_list());
        $disabled = array_filter($unprotected,'plugin_isdisabled');
        //TODO bad fix: get better sorting.
        $this->plugins['enabled'] = array_map(array($this,'_info_list'),$enabled);
        usort($this->plugins['enabled'],array($this,'_sort'));
        $this->plugins['disabled'] = array_map(array($this,'_info_list'),$disabled);
        usort($this->plugins['disabled'],array($this,'_sort'));
        $this->protected_plugins = array_map(array($this,'_info_list'),$plugin_protected);
        usort($this->protected_plugins,array($this,'_sort'));
    }

    function html() {
        global $lang;
        $this->html_menu();
        print $this->manager->locale_xhtml('admin_plugin');
        $this->render_search('pm__search',$this->get_lang('search_plugin'));
        /**
         * List plugins
         */
        if(is_array($this->plugins) && count($this->plugins)) {
            $list = new plugins_list($this,'plugins__list',$this->actions_list);
            $list->add_header($this->get_lang('manage'));
            $list->start_form();
            foreach($this->plugins as $type => $plugins) {
                foreach($plugins as $info) {
                    $class = $this->get_class($info,$type);
                    $actions = $this->get_actions($info,$type);
                    $list->add_row($class,$info,$actions);
                }
            }
            $list->end_form();
            $list->render();
        }
        if(is_array($this->protected_plugins) && count($this->protected_plugins)) {
            $protected_list = new plugins_list($this,'plugins__protected');
            $protected_list->add_header($this->get_lang('protected_head'));
            $protected_list->add_p($this->get_lang('protected_desc'));  
            $protected_list->start_form();          
            $checkbox = array('disabled'=>'disabled');
            foreach($this->protected_plugins as $info) {
                $class = $this->get_class($info,"protected");
                $actions = $this->get_actions($info,'protected');
                $protected_list->add_row($class,$info,$actions,$checkbox);
            }
            $protected_list->rowadded =false;
            $protected_list->end_form();
            $protected_list->render();
        }
        //end list plugins
    }
    protected function show_results() {
        if(is_array($this->result) && count($this->result)) {
            foreach($this->result as $outcome => $changed_plugins)
                if(is_array($changed_plugins) && count($changed_plugins))
                    array_walk($changed_plugins,array($this,'say_'.$outcome));
        }
    }

    function get_actions($info,$type) {
        $actions = $this->make_action('info',$info->id,$this->get_lang('btn_info'));
        if(!empty($info->newversion) || stripos($info->version,$this->get_lang('unknown'))!==false)
            $actions .= ' | '.$this->make_action('update',$info->id,$this->get_lang('btn_update'));
        elseif(!in_array($info->id,$this->_bundled))
            $actions .= ' | '.$this->make_action('update',$info->id,$this->get_lang('btn_reinstall'));
        if($type =="enabled")
            $actions .= ' | '.$this->make_action('disable',$info->id,$this->get_lang('btn_disable'));
        elseif($type == 'disabled')
            $actions .= ' | '.$this->make_action('enable',$info->id,$this->get_lang('enable'));
        if(!in_array($info->id,$this->_bundled))
            $actions .= ' | '.$this->make_action('delete',$info->id,$this->get_lang('btn_delete'));
        return $actions;
    }

    function get_checkbox($type) {
        if($type == 'protected') return array('disabled'=>'disabled');
        return array();
    }
    function get_class( $info,$class) {
        if(!empty($info->securityissue)) $class .= ' secissue';
        if($info->id === $this->showinfo) $class .=" infoed";
        return $class;
    }
}
