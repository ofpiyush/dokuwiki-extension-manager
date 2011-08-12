<?php
class pm_enable_action extends pm_base_action {

    var $result = array();
    function act() {
        $disabled = array_filter($this->plugin,'plugin_isdisabled');
        if(is_array($disabled) && count($disabled)) {
            $this->result['enabled']      = array_filter($disabled,'plugin_enable');
            $this->result['notenabled']   = array_diff_key($disabled,$this->result['enabled']);
        }
        $this->show_results();
        $this->refresh();
    }

    function say_enabled($plugin,$key) {
        msg(sprintf($this->m->getLang('enabled'),$plugin),1);
    }

    function say_notenabled($plugin,$key) {
        msg(sprintf($this->m->getLang('notenabled'),$plugin),-1);
    }
}
