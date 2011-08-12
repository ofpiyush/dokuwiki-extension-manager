<?php
class pm_delete_action extends pm_base_action {

    var $result = array();
    var $type = "plugin";

    function act() {
        global $conf;
        $plugins = array_diff($this->plugin,array_merge($this->m->_bundled,array($conf['template'])));
        $this->type = !empty($_REQUEST['template']) ? 'template' : 'plugin';
        if(is_array($plugins) && count($plugins)) {
            $this->result[$this->type.'deleted']      = array_filter($plugins,array($this,'delete'));
            $this->result[$this->type.'notdeleted']   = array_diff_key($plugins,$this->result[$this->type.'deleted']);
            $list = $this->type.'_list';
            $this->m->$list = array_diff($this->m->$list,$this->result[$this->type.'deleted']);
        }
        $this->show_results();
        $this->refresh($this->type);
    }

    function delete($plugin) {
        if($this->type == "plugin")
            $path = DOKU_PLUGIN.plugin_directory($plugin);
        else
            $path = DOKU_INC.'lib/tpl/'.$plugin;
        return $this->dir_delete($path);
    }

    function say_plugindeleted($plugin,$key) {
        msg(sprintf($this->m->getLang('deleted'),$plugin),1);
    }

    function say_pluginnotdeleted($plugin,$key) {
        msg(sprintf($this->m->getLang('error_delete'),$plugin),-1);
    }
    function say_templatedeleted($plugin,$key) {
        msg(sprintf($this->m->getLang('template_deleted'),$plugin),1);
    }

    function say_templatenotdeleted($plugin,$key) {
        msg(sprintf($this->m->getLang('template_error_delete'),$plugin),-1);
    }
}
