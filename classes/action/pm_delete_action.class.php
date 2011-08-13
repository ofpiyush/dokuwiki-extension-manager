<?php
class pm_delete_action extends pm_base_action {

    var $result = array();
    var $type = "plugin";

    function act() {
        global $conf;
        if(in_array($this->m->tab,array('plugin','template'))) {
            $this->result[$this->m->tab.'deleted']      = array_filter($this->plugin,array($this,'delete'));
            $this->result[$this->m->tab.'notdeleted']   = array_diff($this->plugin,$this->result[$this->type.'deleted']);
            $this->show_results();
            $this->refresh($this->m->tab);
            $list = $this->m->tab.'_list';
            $this->m->$list = array_diff($this->m->$list,$this->result[$this->type.'deleted']);
        }
    }

    function delete($plugin) {
        $info = $this->m->info->get($plugin,$this->m->tab);
        if($info->is_template)
            $path = DOKU_INC.'lib/tpl/'.$plugin;
        else
            $path = DOKU_PLUGIN.plugin_directory($plugin);
        if(!$info->can_delete()) return false;
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
