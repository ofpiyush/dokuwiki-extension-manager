<?php
class pm_disdown_action extends pm_download_action {

    function down() {
        parent::down();
        if(isset($this->downloaded['plugin']) && is_array($this->downloaded['plugin']))
            array_filter($this->downloaded['plugin'],'plugin_disable');
    }
}
