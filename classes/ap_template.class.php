<?php
class ap_template extends ap_manage {

    function process() {
        //TODO pull up plugins list type 32 or Temnplate from the cache!!!
    }

    function html() {
        $this->html_menu();
        global $ID,$lang;
        ptln('<div class="common">');
        ptln('  <h2>'.$this->lang['download'].'</h2>');// get to template.
        ptln('  <form action="'.wl($ID,array('do'=>'admin','page'=>'plugin')).'" method="post">');
        ptln('    <fieldset class="hidden">',4);
        formSecurityToken();
        ptln('      <input type="hidden" name="ext[type]" value="Template" />');
        ptln('    </fieldset>');
        ptln('    <fieldset>');
        ptln('      <legend>'.$lang['btn_search'].'</legend>');
        ptln('      <label for="dw__search">'.$lang['btn_search'].'<input name="term" id="dw__search" class="edit" type="text" maxlength="200" /></label>');
        ptln('      <input type="submit" class="button" name="fn[search]" value="'.$lang['btn_search'].'" />');
        ptln('    </fieldset>');
        ptln('  </form>');
        ptln('</div>');
        //TODO bring out a decent layout in grid, with screenshots hotlinked form servers?
    }
}
