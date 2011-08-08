<?php
class ap_delete extends ap_plugin {

    var $result = array();
    var $type = "plugin";

    function process() {
        $plugins = array_diff($this->plugin,$this->_bundled);
        $this->type = !empty($_REQUEST['template']) ? 'template' : 'plugin';
        if(is_array($plugins) && count($plugins)) {
            $this->result[$this->type.'deleted']      = array_filter($plugins,array($this,'delete'));
            $this->result[$this->type.'notdeleted']   = array_diff_key($plugins,$this->result[$this->type.'deleted']);
            $this->manager->plugin_list   = array_diff($this->manager->plugin_list,$this->result[$this->type.'deleted']);
        }
        $this->show_results();
        $this->refresh($this->type);
        parent::process();
    }

    function delete($plugin) {
        if($this->type == "plugin")
            $path = DOKU_PLUGIN.plugin_directory($plugin);
        else
            $path = DOKU_INC.'lib/tpl/'.$plugin;
        return $this->dir_delete($path);
    }

    function say_plugindeleted($plugin,$key) {
        msg(sprintf($this->lang['deleted'],$plugin),1);
    }

    function say_pluginnotdeleted($plugin,$key) {
        msg(sprintf($this->lang['error_delete'],$plugin),-1);
    }
    function say_templatedeleted($plugin,$key) {
        msg(sprintf("Template %s deleted",$plugin),1);
    }

    function say_templatenotdeleted($plugin,$key) {
        msg(sprintf("Template %s count not be deleted",$plugin),-1);
    }
}
