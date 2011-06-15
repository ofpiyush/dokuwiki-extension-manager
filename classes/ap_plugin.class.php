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
            /*ptln('<form action="'.wl($ID,array('do'=>'admin','page'=>'plugin')).'" method="post" class="plugins">');
            ptln('  <fieldset class="hidden">');
            formSecurityToken();
            ptln('  </fieldset>');
            */

            $form = new Doku_Form(array('id'=>'test', 'action' => wl($ID,array('do'=>'admin','page'=>'plugin'))));
            html_form('TEST',$form);
            print_r($this->manager->plugin_list);
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
}
