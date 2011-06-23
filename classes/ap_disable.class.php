<?php
class ap_disable extends ap_manage {

    function process() {
        $enabled = array_filter($this->plugin,array($this,'isdisabled'));
        if(is_array($enabled) && count($enabled)) {
            $result['disabled']      = array_filter($enabled,'plugin_disable');
            $result['notdisabled']   = array_diff_key($enabled,$result['disabled']);
            foreach($result as $outcome => $plugins)
                if(is_array($plugins) && count($plugins))
                    array_walk($plugins,array($this,'say_'.$outcome));
            $this->refresh();
        }
    }

    function html() {}

    function isdisabled($plugin) {
        global $plugin_protected;
        if(in_array($plugin,$plugin_protected))
            return false;
        else
            return !plugin_isdisabled($plugin);
    }

    function say_disabled($plugin,$key) {
        msg(sprintf($this->lang['disabled'],$plugin),1);
    }

    function say_notdisabled($plugin,$key) {
        msg(sprintf($this->lang['notdisabled'],$plugin),-1);
    }
}

