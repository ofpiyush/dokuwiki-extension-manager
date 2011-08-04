<?php
require_once(DOKU_PLUGIN."plugin/classes/ap_download.class.php");
class ap_disdown extends ap_download {

    function down() {
        parent::down();
        array_filter($this->downloaded,'plugin_disable');
    }
}
