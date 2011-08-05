<?php

class ap_plugin extends ap_manage {
    var $plugins;
    var $protected_plugins;
    var $actions_list;

    function process() {
        global $plugin_protected;
        $this->actions_list = array(
            'enable'=>'Enable',//TODO add language
            'disable'=>'Disable',//TODO add language
            'delete'=>'Delete',//TODO add language
            'update'=>'Update'//TODO add language
            );
        $list = $this->manager->plugin_list;
        $unprotected = array_diff($list,$plugin_protected);
        $enabled = array_intersect($unprotected,plugin_list());
        $disabled = array_filter($unprotected,'plugin_isdisabled'); //TODO check array_diff/array_intersect vs array_filter speeds
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
        $this->show_results();
        $this->render_search('pm__search','Search for a new plugin');
        /**
         * List plugins
         */
        ptln('<h2>'.$this->lang['manage'].'</h2>');
        if(is_array($this->plugins) && count($this->plugins)) {
            $list = new plugins_list('plugins_list',$this->actions_list);
            foreach($this->plugins as $type => $plugins) {
                foreach($plugins as $info) {
                    $class = $type;
                    if((array_key_exists('securityissue',$info) && !empty($info['securityissue'])) )
                        $class .= " secissue";
                    $actions = $this->make_action('info',$info['id'],'Info');
                    if($type =="enabled")
                        $actions .= ' | '.$this->make_action('disable',$info['id'],'Disable');
                    else
                        $actions .= ' | '.$this->make_action('enable',$info['id'],'Enable');
                    $actions .= ' | '.$this->make_action('delete',$info['id'],'Delete');
                    $list->add_row($class,$info,$actions);
                }
            }
            $list->render('PLUGIN_MANAGER');
        }

        if(is_array($this->protected_plugins) && count($this->protected_plugins)) {
            ptln('<div id="plugins__protected">');
            ptln('  <h2>Protected Plugins</h2>');
            ptln('  <p>');
            ptln('  These plugins are protected and should not be disabled and/or deleted. They are intrinsic to DokuWiki.');
            ptln('  </p>');
            ptln('  <table class="inline">');
            foreach($this->protected_plugins as $info) {
                ptln('    <tr class="protected">');
                ptln('      <td class="checkbox"><input type="checkbox" checked="checked" disabled="disabled" /></td>');
                ptln('      <td class="legend">');
                ptln('        <span class="head">'.$list->make_title($info).'</span>');
                if(isset($info['description'])) {
                    ptln('        <p>'.hsc($info['description']).'</p>');
                }
                ptln('      </td>');
                ptln('      <td class="actions">');
                ptln('        <p>'.$this->make_action('info',$info['id'],'Info').'</p>');
                ptln('      </td>');
                ptln('    </tr>');
            }
            ptln('  </table>');
            ptln('</div>');
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

    protected function _info_list($index) {
        $info  = DOKU_PLUGIN.'/'.$index.'/plugin.info.txt';
        $return = array('id'=>$index,'name' => $index,'base'=>$index);
        if(@file_exists($info)) {
            $return = array_merge($return,confToHash($info));
        } 
        /* TODO for #25 "getInfo() not supported" discuss the issue with having components
        else {
            $components = $this->get_plugin_components($index);
            $load = plugin_load()
        }
        */
        $return = array_key_exists($return['base'],$this->repo) ? array_merge($return,$this->repo[$return['base']]) : $return;
        if(array_key_exists('desc',$return) && strlen($return['desc']))
            $return['description'] = $return['desc'];
        $return['id'] = $index;
        return $return;
    }
    protected function _sort($a,$b) {
        return strcmp($a['name'],$b['name']);
    }

}
