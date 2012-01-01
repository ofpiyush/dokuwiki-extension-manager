<?php
class pm_enable_action extends pm_base_action {

    var $result = array();
    function act() {
        if(is_array($this->plugin) && count($this->plugin)) {
            $this->result['enabled']      = array_filter($this->plugin,array($this,'enable'));
            $this->result['notenabled']   = array_diff_key($this->plugin,$this->result['enabled']);
            $this->show_results();
        }
        $this->refresh($this->manager->tab);
    }

    function enable($plugin) {
        $info = $this->manager->info->get($plugin,$this->manager->tab);
        if(!$info->can_enable()) return false;
        if($info->is_template) {
            return $this->template_enable($plugin);
        } else {
            return plugin_enable($plugin);
        }
    }
    function say_enabled($plugin,$key) {
        msg(sprintf($this->manager->getLang('enabled'),$plugin),1);
    }

    function say_notenabled($plugin,$key) {
        msg(sprintf($this->manager->getLang('notenabled'),$plugin),-1);
    }

    // TODO remove ugly temporary fix for switching template
    function template_enable($plugin) {
        global $config_cascade;

        $localconfig = end($config_cascade['main']['local']);
        $cfg = file_get_contents($localconfig);
        if (preg_match("/conf\['template'\]/",$cfg)) {
            $cfg = preg_replace("/(conf\['template'\]\s*=\s*').*?(';)/", '$1'.hsc($plugin).'$2', $cfg);
        } else {
            $cfg .= "\$conf['template'] = '".hsc($plugin)."';\n";
        }
        file_put_contents($localconfig, $cfg);
        return true;
    }
}
