<?php

class plugin_single extends base_single {
    private $cascade = array('default'=>array(),'local'=>array(),'protected'=>array());
    protected function setup() {
        $this->cascade = plugin_getcascade();
        $this->is_bundled = in_array($this->id,$this->b->_bundled); 
    }
    function can_enable() {
        return (!array_key_exists($this->id,$this->cascade['protected']));
    }

    function can_disable() {
        return (!array_key_exists($this->id,$this->cascade['protected']));
    }
}
