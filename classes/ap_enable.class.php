<?php
class ap_enable extends ap_plugin {

    var $result = array();
    function process() {
        $disabled = array_filter($this->plugin,'plugin_isdisabled');
        if(is_array($disabled) && count($disabled)) {
            $this->result['enabled']      = array_filter($disabled,'plugin_enable');
            $this->result['notenabled']   = array_diff_key($disabled,$this->result['enabled']);
        }
        $this->show_results();
        $this->refresh();
        parent::process();
    }

    function say_enabled($plugin,$key) {
        msg(sprintf($this->lang['enabled'],$plugin),1);
    }

    function say_notenabled($plugin,$key) {
        msg(sprintf($this->lang['notenabled'],$plugin),-1);
    }
}

