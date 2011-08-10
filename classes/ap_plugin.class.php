<?php

class ap_plugin extends ap_manage {
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
        if(!empty($_REQUEST['info']))
            $this->showinfo = $_REQUEST['info'];
        if(!empty($this->showinfo) && in_array($this->showinfo,$list)) {
            $list = array_diff($list,array($this->showinfo));
            $infoed = $this->showinfo;
            $this->showinfo =array();
            $this->showinfo['protected'] = in_array($infoed,$plugin_protected);
            $this->showinfo['status'] = plugin_isdisabled($infoed)? 'disabled' : 'enabled';
            $this->showinfo['info'] = $this->_info_list($infoed,'plugin',true);
        } else {
            $this->showinfo = null;
        }
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
            if(!empty($this->showinfo) && !$this->showinfo['protected']) {
                $class  = $this->get_class($this->showinfo['info'],$this->showinfo['status']." infoed");
                $actions = $this->get_actions($this->showinfo['info'],$this->showinfo['status']);
                $list->add_row($class,$this->showinfo['info'],$actions);
                unset($this->showinfo);
            }
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
            if(!empty($this->showinfo) && $this->showinfo['protected']) {
                $class = $this->get_class($this->showinfo['info'],"infoed");
                $actions = $this->get_actions($this->showinfo['info'],'protected');
                $protected_list->add_row($class,$this->showinfo['info'],$actions,$checkbox);
            }
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

    function get_actions(array $info,$type) {
        $actions = $this->make_action('info',$info['id'],$this->get_lang('btn_info'));
        if(!empty($info['newversion']) || stripos($info['version'],$this->get_lang('unknown'))!==false)
            $actions .= ' | '.$this->make_action('update',$info['id'],$this->get_lang('btn_update'));
        if($type =="enabled")
            $actions .= ' | '.$this->make_action('disable',$info['id'],$this->get_lang('btn_disable'));
        elseif($type == 'disabled')
            $actions .= ' | '.$this->make_action('enable',$info['id'],$this->get_lang('enable'));
        if(!in_array($info['id'],$this->_bundled))
            $actions .= ' | '.$this->make_action('delete',$info['id'],$this->get_lang('btn_delete'));
        return $actions;
    }

    function get_checkbox($type) {
        if($type == 'protected') return array('disabled'=>'disabled');
        return array();
    }
    function get_class(array $info,$class) {
        if(!empty($info['securityissue'])) $class .= ' secissue';
        return $class;
    }
}
