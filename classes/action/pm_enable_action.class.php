<?php
/**
 * Enable action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_enable_action extends pm_base_action {

    var $result = array();
    function act() {
        if(is_array($this->selection)) {
            array_walk($this->selection,array($this,'enable'));
        }
        $this->refresh($this->manager->tab);
    }

    function enable($cmdkey) {
        $info = $this->manager->info->get($cmdkey);
        if(!$info->can_enable()) return false;

        if($info->is_template) {
            $func = 'template_enable';
        } else {
            $func = 'plugin_enable';
        }

        if ($this->$func($info->id)) {
            $this->report(1,$info,'enabled');
            return true;
        } else {
            $this->report(-1,$info,'notenabled');
            return false;
        }
    }

    function plugin_enable($plugin) {
        return plugin_enable($plugin);
    }

    // TODO remove ugly temporary fix for switching template
    function template_enable($template) {
        global $config_cascade;

        $localconfig = end($config_cascade['main']['local']);
        $cfg = file_get_contents($localconfig);
        if (preg_match("/conf\['template'\]/",$cfg)) {
            $cfg = preg_replace("/(conf\['template'\]\s*=\s*').*?(';)/", '$1'.hsc($template).'$2', $cfg);
        } else {
            $cfg .= "\$conf['template'] = '".hsc($template)."';\n";
        }
        file_put_contents($localconfig, $cfg);
        return true;
    }
}
