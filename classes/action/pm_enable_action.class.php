<?php
class pm_enable_action extends pm_base_action {

    var $result = array();
    function act() {
        if(is_array($this->plugin) && count($this->plugin)) {
            $this->result['enabled']      = array_filter($this->plugin,array($this,'enable'));
            $this->result['notenabled']   = array_diff_key($this->plugin,$this->result['enabled']);
            $this->show_results();
        }
        $this->refresh($this->manager->tab);
    }

    function enable($plugin) {
        $info = $this->manager->info->get($plugin,$this->manager->tab);
        if(!$info->can_enable()) return false;
        return plugin_enable($plugin);
    }
    function say_enabled($plugin,$key) {
        msg(sprintf($this->manager->getLang('enabled'),$plugin),1);
    }

    function say_notenabled($plugin,$key) {
        msg(sprintf($this->manager->getLang('notenabled'),$plugin),-1);
    }
}
