<?php
require_once(DOKU_PLUGIN."plugin/classes/ap_download.class.php");
class ap_disdown extends ap_download {

    function down() {
        parent::down();
        if(isset($this->downloaded['plugin']) && is_array($this->downloaded['plugin']))
            array_filter($this->downloaded['plugin'],'plugin_disable');
    }
}
