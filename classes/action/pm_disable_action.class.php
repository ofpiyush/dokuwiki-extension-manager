<?php
class pm_disable_action extends pm_base_action {

    var $result = array();

    function act() {
        if($this->m->tab == 'plugin') {
            $this->result['disabled']      = array_filter($this->plugin,array($this,'disable'));
            $this->result['notdisabled']   = array_diff_key($this->plugin,$this->result['disabled']);
            $this->show_results();
            $this->refresh($this->m->tab);
        }
    }

    function disable($plugin) {
        $info = $this->m->info->get($plugin,$this->m->tab);
        if(!$info->can_disable()) return false;
        return plugin_disable($plugin);
    }
    function say_disabled($plugin,$key) {
        msg(sprintf($this->m->getLang('disabled'),$plugin),1);
    }

    function say_notdisabled($plugin,$key) {
        msg(sprintf($this->m->getLang('notdisabled'),$plugin),-1);
    }
}

