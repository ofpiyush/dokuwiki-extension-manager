<?php
/**
 * Disable action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_disable_action extends pm_base_action {

    var $result = array();

    function act() {
        if($this->manager->tab == 'plugin') {
            $this->result['disabled']      = array_filter($this->plugin,array($this,'disable'));
            $this->result['notdisabled']   = array_diff_key($this->plugin,$this->result['disabled']);
            $this->show_results();
        }
        $this->refresh($this->manager->tab);
    }

    function disable($plugin) {
        $info = $this->manager->info->get($plugin,$this->manager->tab);
        if(!$info->can_disable()) return false;
        return plugin_disable($plugin);
    }
    function say_disabled($plugin,$key) {
        msg(sprintf($this->manager->getLang('disabled'),$plugin),1);
    }

    function say_notdisabled($plugin,$key) {
        msg(sprintf($this->manager->getLang('notdisabled'),$plugin),-1);
    }
}

