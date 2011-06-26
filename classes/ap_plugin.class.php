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
        $this->repo = $this->fetch_cache();
        $this->plugins = array_map(array($this,'_info_list'),$enabled+$disabled);
        $this->protected_plugins = array_map(array($this,'_info_list'),$plugin_protected);
        //TODO pull up plugins list type 32 or Temnplate from the cache!!!
    }

    function html() {
        global $lang;
        $this->html_menu();
        print $this->manager->locale_xhtml('admin_plugin');
        ptln('<div class="common">');
        ptln('  <h2>Search for a new plugin</h2>');//TODO Add language
        $search_form = new Doku_Form('search');
        $search_form->startFieldset($lang['btn_search']);
        $search_form->addElement(form_makeTextField('term','',$lang['btn_search'],'dw__search'));
        $search_form->addHidden('page','plugin');
        $search_form->addHidden('tab','search');
        $search_form->addHidden('fn[search]',$lang['btn_search']);
        $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
        $search_form->endFieldset();
        $search_form->printForm();
        ptln('</div>');
        /**
         * List plugins
         */
        ptln('<h2>'.$this->lang['manage'].'</h2>');
        if(is_array($this->plugins) && count($this->plugins)) {
            $form = new Doku_Form("plugins");
            $form->addHidden('page','plugin');
            $form->addHidden('fn[multiselect]','Multiselect');
            $form->addElement(form_makeOpenTag('div',array('class'=>'top')));
            $form->addElement(form_makeOpenTag('label',array('class'=>'checkbox')));
            $form->addElement('Sel');//TODO Add language
            $form->addElement(form_makeCloseTag('label'));
            $form->addElement(form_makeOpenTag('label',array('class'=>'legend')));
            $form->addElement(form_makeOpenTag('label',array('class'=>'head')));
            $form->addElement(rtrim($this->lang['name'],":"));
            $form->addElement(form_makeCloseTag('label'));
            $form->addElement(form_makeCloseTag('label'));
            $form->addElement(form_makeOpenTag('div',array('class'=>'actions')));
            $form->addElement('Actions');//TODO Add language
            $form->addElement(form_makeCloseTag('div'));
            $form->addElement(form_makeCloseTag('div'));
            foreach($this->plugins as $info) {
                $form->startFieldset($info['id']);
                //for now add the names at least (after filtering, the plugins with no plugin info come at bottom)
                $form->addElement(form_makeCheckboxField('checked[]',$info['id'],'','','checkbox'));
                $form->addElement(form_makeOpenTag('label',array('class'=>'legend')));
                $form->addElement(form_makeOpenTag('label',array('class'=>'head')));
                $form->addElement($info['name']);
                $form->addElement(form_makeCloseTag('label'));
                if(isset($info['desc'])) {
                    $form->addElement(form_makeOpenTag('p'));
                    $form->addElement($info['desc']);
                    $form->addElement(form_makeCloseTag('p'));
                }
                $form->addElement(form_makeCloseTag('label'));
                $form->addElement(form_makeOpenTag('div',array('class'=>'actions')));
                $form->addElement('Info | Report broken | Delete');//TODO Make some way of keeping imploded actions && Add language
                $form->addElement(form_makeCloseTag('div'));
                $form->endFieldset();
            }
            //TODO add a div
            $form->addElement(form_makeMenuField('action',array(
                                                                ''=>'Action',//TODO add langugae
                                                                'enable'=>'Enable',//TODO add language
                                                                'disable'=>'Disable',//TODO add language
                                                                'delete'=>'Delete',//TODO add language
                                                                'update'=>'Update'//TODO add language
                                                                )
                                                                ,'','With Selected: '));//TODO add language
            $form->addElement(form_makeButton('submit', 'admin', 'Go' ));
            html_form('PLUGIN_MANAGER',$form);
        }
        echo "<pre>";
            print_r($this->protected_plugins);
        echo "</pre>";
        //TODO Make UI for protected plugins
        //end list plugins
    }

    protected function _info_list($index) {
        $info  = DOKU_PLUGIN.'/'.$index.'/plugin.info.txt';
        $hash = (@file_exists($info))? confToHash($info): array('id'=>$index,'name' => $index);
        $return = array_key_exists($index,$this->repo) ? array_merge($hash,$this->repo[$index]) : $hash;
        if(!array_key_exists('desc',$return) && array_key_exists('description',$return))
            $return['desc'] = $return['description'];
        return $return;
    }

}
