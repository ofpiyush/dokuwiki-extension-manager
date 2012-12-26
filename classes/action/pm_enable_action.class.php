<?php
/**
 * Enable action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
if(class_exists('admin_plugin_config')) {
    require_once(DOKU_PLUGIN.'config/settings/config.class.php');  // main configuration class and generic settings classes
    require_once(DOKU_PLUGIN.'config/settings/extra.class.php');   // settings classes specific to these settings
}

class pm_enable_action extends pm_base_action {

    protected function act() {
        if(is_array($this->selection)) {
            array_walk($this->selection,array($this,'enable'));
        }
        $this->refresh($this->manager->tab);
    }

    private function enable($cmdkey) {
        $info = $this->helper->info->get($cmdkey);
        if(!$info->can_enable()) return false;

        if($info->is_template) {
            $func = 'template_enable';
        } else {
            $func = 'plugin_enable';
        }

        if($this->$func($info->id)) {
            $this->report(1,$info,'enabled');
            return true;
        } else {
            $this->report(-1,$info,'notenabled');
            return false;
        }
    }

    private function plugin_enable($plugin) {
        return plugin_enable($plugin);
    }

    private function template_enable($template) {
        if(!class_exists('admin_plugin_config')) return false;

        $config = new configuration(DOKU_PLUGIN.'config/settings/config.metadata.php');

        if($config->setting['template']->update($template)) {
            return $config->save_settings('Extension manager');
        }
        return false;
    }
}
