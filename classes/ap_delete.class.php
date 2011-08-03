<?php
class ap_delete extends ap_plugin {

    var $result = array();

    function process() {
        $plugins = array_diff($this->plugin,$this->_bundled);
        if(is_array($plugins) && count($plugins)) {
            $this->result['deleted']      = array_filter($plugins,array($this,'delete'));
            $this->result['notdeleted']   = array_diff_key($plugins,$this->result['deleted']);
            $this->manager->plugin_list   = array_diff($this->manager->plugin_list,$this->result['deleted']);
            //remove from plugins.local.php
            array_filter($this->result['deleted'],'plugin_enable');
        }
        parent::process();
    }

    function delete($plugin) {
        return $this->dir_delete(DOKU_PLUGIN.plugin_directory($plugin));
    }

    function say_deleted($plugin,$key) {
        msg(sprintf($this->lang['deleted'],$plugin),1);
    }

    function say_notdeleted($plugin,$key) {
        msg(sprintf($this->lang['error_delete'],$plugin),-1);
    }
}
