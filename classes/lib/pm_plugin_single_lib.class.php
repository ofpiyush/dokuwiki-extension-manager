<?php

class pm_plugin_single_lib extends pm_base_single_lib {
    private $cascade = array('default'=>array(),'local'=>array(),'protected'=>array());
    protected function setup() {
        $this->cascade = plugin_getcascade();
        $this->is_bundled = in_array($this->id,$this->m->_bundled);
    }
    function can_enable() {
        return (!array_key_exists($this->id,$this->cascade['protected']));
    }

    function can_disable() {
        return (!array_key_exists($this->id,$this->cascade['protected']));
    }

    function default_type() {
        $components = get_plugin_components($this->id);
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
