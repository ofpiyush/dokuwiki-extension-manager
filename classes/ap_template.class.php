<?php
class ap_template extends ap_manage {

    function process() {
        //TODO pull up plugins list type 32 or Template from the cache!!!
    }

    function html() {
        $this->html_menu();
        $this->render_search('tpl__search','Search for a new Template','Template');
        //TODO bring out a decent layout in grid, with screenshots hotlinked form servers?
    }
}
