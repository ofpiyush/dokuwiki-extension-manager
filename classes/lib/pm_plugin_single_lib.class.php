<?php

class pm_plugin_single_lib extends pm_base_single_lib {

    /**
     * If plugin is bundled
     * @var bool
     */
    var $is_bundled = false;
    /**
     * If plugin is protected
     * @var bool
     */
    var $is_protected = false;
    /**
     * If plugin is enabled
     * @var bool
     */
    var $is_enabled = false;


    function can_select() {
        return (!$this->is_protected);
    }
    function can_enable() {
        return (!$this->is_protected && !$this->is_enabled);
    }

    function can_disable() {
        return (!$this->is_protected && $this->is_enabled);
    }

    function wrong_folder() {
        if(!empty($this->info['base']) && $this->info['base'] != $this->id) return true;
        return false;
    }

    function default_type() {
        $components = $this->manager->get_plugin_components($this->id);
        $return = "";
        if(!empty($components)) {
            foreach($components as $component) {
                $return .= ', '.$component['type'];
            }
            $this->type = ltrim($return,',');
            return $this->type;
        }
        return false;
    }
}
