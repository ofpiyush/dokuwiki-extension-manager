<?php
class pm_download_disabled_action extends pm_download_action {

    function down() {
        if(is_array($this->plugin)) {
            foreach($this->plugin as $plugin) {
                if(array_key_exists($plugin,$this->manager->repo)) {
                    $info = $this->manager->info->get($plugin,'search');
                    if($info->can_download_disabled()) {
                        $this->download_single($info);
                    }
                }
            }
        }
        if(isset($this->downloaded['plugin']) && is_array($this->downloaded['plugin']))
            array_filter($this->downloaded['plugin'],'plugin_disable');
    }
}
