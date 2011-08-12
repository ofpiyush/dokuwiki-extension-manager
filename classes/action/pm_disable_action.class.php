<?php
class pm_disable_action extends pm_base_action {

    var $result = array();

    function act() {
        global $plugin_protected;
        $unprotected = array_diff($this->plugin,$plugin_protected);
        $enabled = array_filter($unprotected,array($this,'isenabled'));
        if(is_array($enabled) && count($enabled)) {
            $this->result['disabled']      = array_filter($enabled,'plugin_disable');
            $this->result['notdisabled']   = array_diff_key($enabled,$this->result['disabled']);
        }
        $this->show_results();
        $this->refresh();
    }

    function isenabled($plugin) {
        return !plugin_isdisabled($plugin);
    }

    function say_disabled($plugin,$key) {
        msg(sprintf($this->m->getLang('disabled'),$plugin),1);
    }

    function say_notdisabled($plugin,$key) {
        msg(sprintf($this->m->getLang('notdisabled'),$plugin),-1);
    }
}

