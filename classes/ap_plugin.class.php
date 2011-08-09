<?php

class ap_plugin extends ap_manage {
    var $plugins;
    var $protected_plugins;
    var $actions_list;
    var $showinfo;

    function process() {
        global $plugin_protected;
        $this->actions_list = array(
            'enable'=>$this->lang['enable'],
            'disable'=>$this->lang['btn_disable'],
            'delete'=>$this->lang['btn_delete'],
            'update'=>$this->lang['btn_update'],
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
        //TODO pull up plugins list type 32 or Temnplate from the cache!!!
    }

    function html() {
        global $lang;
        $this->html_menu();
        print $this->manager->locale_xhtml('admin_plugin');
        $this->render_search('pm__search',$this->lang['search_plugin']);
        /**
         * List plugins
         */
        ptln('<h2>'.$this->lang['manage'].'</h2>');
        if(is_array($this->plugins) && count($this->plugins)) {
            $list = new plugins_list($this,'plugins_list',$this->actions_list);
            if(!empty($this->showinfo) && !$this->showinfo['protected']) {
                $class  = $this->get_class($this->showinfo['info'],$this->showinfo['status']);
                $class .=" infoed";
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
            $list->render('PLUGIN_PLUGINMANAGER_RENDER_PLUGINSLIST');
        }

        if(is_array($this->protected_plugins) && count($this->protected_plugins)) {
            ptln('<div id="plugins__protected">');
            ptln('  <h2>'.$this->lang['protected_head'].'</h2>');
            ptln('  <p>');
            ptln($this->lang['protected_desc']);
            ptln('  </p>');
            ptln('  <table class="inline">');
            if(!empty($this->showinfo) && $this->showinfo['protected']) {
                $this->add_protected_row($this->showinfo['info'],$list," infoed");
            }
            foreach($this->protected_plugins as $info) {
                $this->add_protected_row($info,$list);
            }
            ptln('  </table>');
            ptln('</div>');
        }
        //end list plugins
    }

    function add_protected_row($info,$list,$class="") {
        ptln('    <tr class="protected'.$class.'">');
        ptln('      <td class="checkbox"><input type="checkbox" checked="checked" disabled="disabled" /></td>');
        ptln('      <td class="legend">');
        ptln('        <span class="head">'.$list->make_title($info).'</span>');
        if(stripos($class,'infoed') !== false) {
            ptln('<span class="inforight"><p>');
            if(!empty($info['author'])) {
                if(!empty($info['email']))
                    ptln('<strong>'.hsc($this->lang['author']).'</strong> <a href="mailto:'.hsc($info['email']).'">'.hsc($info['author']).'</a><br/>');
                else
                    ptln('<strong>'.hsc($this->lang['author']).'</strong> '.hsc($info['author']).'<br/>');
            }
            if(!empty($info['tags']))
                ptln('<strong>'.hsc($this->lang['tags']).'</strong> '.hsc(implode(', ',(array)$info['tags']['tag'])).'<br/>');
            ptln('</p></span>');
        }
        if(isset($info['description'])) {
            ptln('        <p>'.hsc($info['description']).'</p>');
        }
        if(stripos($class,'infoed') !== false) {
            ptln('<p>');
            if(!empty($info['type'])) {
                ptln('<strong>'.hsc($this->lang['components']).':</strong> '.hsc($info['type']).'<br/>');
            }
            ptln('<p>');
        }
        ptln('      </td>');
        ptln('      <td class="actions">');
        ptln('        <p>'.$this->make_action('info',$info['id'],$this->lang['btn_info']).'</p>');
        ptln('      </td>');
        ptln('    </tr>');
    }
    protected function show_results() {
        if(is_array($this->result) && count($this->result)) {
            foreach($this->result as $outcome => $changed_plugins)
                if(is_array($changed_plugins) && count($changed_plugins))
                    array_walk($changed_plugins,array($this,'say_'.$outcome));
        }
    }

    function get_actions($info,$type) {
        $actions = $this->make_action('info',$info['id'],$this->lang['btn_info']);
        if(!empty($info['newversion']) || stripos($info['version'],$this->lang['unknown'])!==false)
            $actions .= ' | '.$this->make_action('update',$info['id'],$this->lang['btn_update']);
        if($type =="enabled")
            $actions .= ' | '.$this->make_action('disable',$info['id'],$this->lang['btn_disable']);
        else
            $actions .= ' | '.$this->make_action('enable',$info['id'],$this->lang['enable']);
        if(!in_array($info['id'],$this->_bundled))
            $actions .= ' | '.$this->make_action('delete',$info['id'],$this->lang['btn_delete']);
        return $actions;
    }

    function get_class($info,$class) {
        if(!empty($info['securityissue'])) $class .= ' secissue';
        return $class;
    }
}
