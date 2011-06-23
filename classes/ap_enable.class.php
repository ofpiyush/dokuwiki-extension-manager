<?php
class ap_enable extends ap_manage {

    function process() {
        $disabled = array_filter($this->plugin,'plugin_isdisabled');
        if(is_array($disabled) && count($disabled)) {
            $result['enabled']      = array_filter($disabled,'plugin_enable');
            $result['notenabled']   = array_diff_key($disabled,$result['enabled']);
            foreach($result as $outcome => $plugins)
                if(is_array($plugins) && count($plugins))
                    array_walk($plugins,array($this,'say_'.$outcome));
            $this->refresh();
        }
    }

    function html() {}

    function say_enabled($plugin,$key) {
        msg(sprintf($this->lang['enabled'],$plugin),1);
    }

    function say_notenabled($plugin,$key) {
        msg(sprintf($this->lang['notenabled'],$plugin),-1);
    }
}

