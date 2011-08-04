<?php
class ap_template extends ap_manage {

    function process() {
        //TODO pull up plugins list type 32 or Temnplate from the cache!!!
    }

    function html() {
        $this->html_menu();
        global $lang;
        ptln('<div class="common">');
        ptln('  <h2>Search for a new Template</h2>');//TODO Add language
        $template_search = new Doku_Form('tpl__search');
        $template_search->startFieldset($lang['btn_search']);
        $template_search->addElement(form_makeTextField('term','',$lang['btn_search'],'tmp__search'));
        $template_search->addHidden('page','plugin');
        $template_search->addHidden('tab','search');
        $template_search->addHidden('ext[type]','Template');
        $template_search->addHidden('fn[search]',$lang['btn_search']);
        $template_search->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
        $template_search->endFieldset();
        $template_search->printForm();
        ptln('</div>');
        //TODO bring out a decent layout in grid, with screenshots hotlinked form servers?
    }
}
