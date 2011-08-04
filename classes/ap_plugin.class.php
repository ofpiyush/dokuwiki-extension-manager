<?php

class ap_plugin extends ap_manage {
    var $plugins;
    var $protected_plugins;

    function process() {
        global $plugin_protected;
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
        if(is_array($this->result) && count($this->result)) {
            foreach($this->result as $outcome => $changed_plugins)
                if(is_array($changed_plugins) && count($changed_plugins))
                    array_walk($changed_plugins,array($this,'say_'.$outcome));
        }
        ptln('<div class="common">');
        ptln('  <h2>Search for a new plugin</h2>');//TODO Add language
        $search_form = new Doku_Form('pm__search');
        $search_form->startFieldset($lang['btn_search']);
        $search_form->addElement(form_makeTextField('term','',$lang['btn_search'],'pm__sfield'));
        $search_form->addHidden('page','plugin');
        $search_form->addHidden('tab','search');
        $search_form->addHidden('fn','search');
        $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
        $search_form->endFieldset();
        $search_form->printForm();
        ptln('</div>');
        /**
         * List plugins
         */
        ptln('<h2>'.$this->lang['manage'].'</h2>');
        if(is_array($this->plugins) && count($this->plugins)) {
            $form = new Doku_Form("plugins__list");
            $form->addHidden('page','plugin');
            $form->addHidden('fn','multiselect');
            $form->addElement(form_makeOpenTag('table',array('class'=>'inline')));//add table
            foreach($this->plugins as $type => $plugins) {
                foreach($plugins as $info) {
                    $class = $type;
                    if((array_key_exists('securityissue',$info) && !empty($info['securityissue'])) )
                        $class .= " secissue";
                    //if($this->missing_dependency())
                    $form->addElement(form_makeOpenTag('tr',array('class'=>$class)));
                    $form->addElement(form_makeOpenTag('td',array('class'=>'checkbox')));
                    $form->addElement(form_makeCheckboxField('checked[]',$info['id'],'',''));
                    $form->addElement(form_makeCloseTag('td'));
                    $form->addElement(form_makeOpenTag('td',array('class'=>'legend')));
                    $form->addElement(form_makeOpenTag('span',array('class'=>'head')));
                    $form->addElement($this->make_title($info));
                    $form->addElement(form_makeCloseTag('span'));
                    if(isset($info['desc'])) {
                        $form->addElement(form_makeOpenTag('p'));
                        $form->addElement(hsc($info['desc']));
                        $form->addElement(form_makeCloseTag('p'));
                    }
                    $form->addElement(form_makeCloseTag('td'));
                    $form->addElement(form_makeOpenTag('td',array('class'=>'actions')));
                    $form->addElement(form_makeOpenTag('p'));
                    $form->addElement('<a href="'.$this->make_url('info',$info['id']).'">Info</a> | ');
                    if($type =="enabled")
                        $form->addElement('<a href="'.$this->make_url('disable',$info['id']).'">Disable</a> | ');
                    else
                        $form->addElement('<a href="'.$this->make_url('enable',$info['id']).'">Enable</a> | ');
                    $form->addElement('<a href="'.$this->make_url('delete',$info['id']).'">Delete</a>');//TODO Make some way of keeping imploded actions && Add language
                    $form->addElement(form_makeCloseTag('p'));
                    $form->addElement(form_makeCloseTag('td'));
                    $form->addElement(form_makeCloseTag('tr'));
                }
            }
            //TODO add a div
            $form->addElement(form_makeCloseTag('table'));
            $form->addElement(form_makeOpenTag('div',array('class'=>'bottom')));
            $form->addElement(form_makeMenuField('action',array(
                                                                ''=>'-Please choose-',//TODO add langugae
                                                                'enable'=>'Enable',//TODO add language
                                                                'disable'=>'Disable',//TODO add language
                                                                'delete'=>'Delete',//TODO add language
                                                                'update'=>'Update'//TODO add language
                                                                )
                                                                ,'','Action: ','','',array('class'=>'quickselect')));//TODO add language
            $form->addElement(form_makeCloseTag('div'));
            $form->addElement(form_makeButton('submit', 'admin', 'Go' ));
            html_form('PLUGIN_MANAGER',$form);
        }

        if(is_array($this->protected_plugins) && count($this->protected_plugins)) {
            ptln('<div id="plugins__protected">');
            ptln('  <h2>Protected Plugins</h2>');
            ptln('  <p>');
            ptln('  These plugins are protected and should not be disabled and/or deleted. They are intrinsic to DokuWiki.');
            ptln('  </p>');

            ptln('  <table class="inline">');
            foreach($this->protected_plugins as $info) {
                ptln('  <tr class="protected">');
                //TODO: switch to tables remove the quickfix
                ptln('    <td class="checkbox"><input type="checkbox" checked="checked" disabled="disabled" /></td>');
                ptln('    <td class="legend">');
                ptln('      <span class="head">'.$this->make_title($info).'</span>');
                if(isset($info['desc'])) {
                    ptln('      <p>'.hsc($info['desc']).'</p>');
                }
                ptln('    </td>');
                ptln('    <td class="actions">');
                ptln('      <p><a href="'.$this->make_url('info',$info['id']).'">Info</a></p>');
                ptln('    </td>');
                ptln('  </tr>');
            }
            ptln('  </table>');
            ptln('</div>');
        }
        //TODO Make UI for protected plugins
        //end list plugins
    }

    protected function _info_list($index) {
        $info  = DOKU_PLUGIN.'/'.$index.'/plugin.info.txt';
        $return =array('id'=>$index,'name' => $index,'base'=>$index);
        $return = (@file_exists($info))? array_merge($return,confToHash($info)): $return;
        $return = array_key_exists($return['base'],$this->repo) ? array_merge($return,$this->repo[$return['base']]) : $return;
        if(!array_key_exists('desc',$return) && array_key_exists('description',$return))
            $return['desc'] = $return['description'];
        $return['id'] = $index;
        return $return;
    }
    protected function _sort($a,$b) {
        return strcmp($a['name'],$b['name']);
    }
    protected function missing_dependency() {
    }

}
