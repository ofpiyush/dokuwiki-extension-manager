<?php
class ap_plugin extends ap_manage {

    function process() {
        //TODO pull up plugins list type 32 or Temnplate from the cache!!!
    }

    function html() {
        global $ID,$lang;
        $this->html_menu();
        print $this->manager->locale_xhtml('admin_plugin');
        ptln('<div class="common">');
        ptln('  <h2>Search for a new plugin</h2>');//TODO Add language
        ptln('  <form action="'.wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search')).'" method="post">');
        ptln('    <fieldset class="hidden">',4);
        formSecurityToken();
        ptln('    </fieldset>');
        ptln('    <fieldset>');
        ptln('      <legend>'.$lang['btn_search'].'</legend>');
        ptln('      <label for="dw__search">'.$lang['btn_search'].'<input name="term" id="dw__search" class="edit" type="text" maxlength="200" /></label>');
        ptln('      <input type="submit" class="button" name="fn[search]" value="'.$lang['btn_search'].'" />');
        ptln('    </fieldset>');
        ptln('  </form>');
        ptln('</div>');
        /**
         * List plugins
         */
            ptln('<h2>'.$this->lang['manage'].'</h2>');
            $list = $this->_info_list($this->manager->plugin_list);
            //TODO do some array_intersect_key with the repo cache to get all other necessary variables
            
            if(is_array($list) && count($list)) {
                $form = new Doku_Form(array( 'class' =>'plugins','action' => wl($ID,array('do'=>'admin','page'=>'plugin'))));
                foreach($list as $id => $info) {
                    $form->startFieldset($id);
                    //for now add the names at least (after filtering, the plugins with no plugin info come at bottom)
                    $form->addElement('<h3 class="legend">'.((!is_null($info))? $info['name'] : $id).'</h3>');
                    $form->endFieldset();
                }
                html_form('TEST',$form);
            }
            //$this->html_pluginlist();

            /*
            ptln('  <fieldset class="buttons">');
            ptln('    <input type="submit" class="button" name="fn[enable]" value="'.$this->lang['btn_enable'].'" />');
            ptln('  </fieldset>');

            //            ptln('  </div>');
            ptln('</form>');
            */
        //end list plugins
    }
    protected function _info_list($list) {
        foreach($list as $index) {
            $info  = DOKU_PLUGIN.'/'.$index.'/plugin.info.txt';
            $newlist[$index] = (@file_exists($info))? confToHash($info): null;
        }
        return $newlist;
    }
    /**
    TODO remove this when done looking at and learning from!
    function html_pluginlist() {
        global $ID;
        global $plugin_protected;

        foreach ($this->manager->plugin_list as $plugin) {

            $disabled = plugin_isdisabled($plugin);
            if(in_array($plugin,$plugin_protected)) {
                $protected[] = $plugin;
                continue;
            }

            $checked = ($disabled) ? '' : ' checked="checked"';

            // determine display class(es)
            $class = array();
            if (in_array($plugin, $this->downloaded)) $class[] = 'new';
            if ($disabled) $class[] = 'disabled';

            $class = count($class) ? ' class="'.implode(' ', $class).'"' : '';

            ptln('    <fieldset'.$class.'>');
            ptln('      <legend>'.$plugin.'</legend>');
            ptln('      <input type="checkbox" class="enable" name="enabled[]" value="'.$plugin.'"'.$checked.' />');
            ptln('      <h3 class="legend">'.$plugin.'</h3>');

            $this->html_button($plugin, 'info', false, 6);
            if (in_array('settings', $this->manager->functions)) {
                $this->html_button($plugin, 'settings', !@file_exists(DOKU_PLUGIN.$plugin.'/settings.php'), 6);
            }
            $this->html_button($plugin, 'update', !$this->plugin_readlog($plugin, 'url'), 6);
            $this->html_button($plugin, 'delete', false,6);

            ptln('    </fieldset>');
        }
    }
    
    function html_button($plugin, $btn, $disabled=false, $indent=0) {
        $disabled = ($disabled) ? 'disabled="disabled"' : '';
        ptln('<input type="submit" class="button" '.$disabled.' name="fn['.$btn.']['.$plugin.']" value="'.$this->lang['btn_'.$btn].'" />',$indent);
    }
    */
}
