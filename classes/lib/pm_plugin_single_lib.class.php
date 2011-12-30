<?php
/**
 * Detailed info object for a single __installed__ plugin
 * it also define capabilities like 'can_enable'
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_plugin_single_lib extends pm_base_single_lib {

    function can_select() {
        return (!$this->is_protected);
    }

    function can_enable() {
        return (!$this->is_protected && !$this->is_enabled);
    }

    function can_disable() {
        return (!$this->is_protected && $this->is_enabled);
    }

    /**
     * return list of component types supplied by this plugin
     */
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
