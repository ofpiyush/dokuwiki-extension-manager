<?php
/**
 * Detailed info object for a single __installed__ plugin
 * it also define capabilities like 'can_enable'
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_plugin_single_lib extends pm_base_single_lib {

    function __construct(admin_plugin_extension $manager,$id,$is_template) {
        parent::__construct($manager,$id,$is_template);

        $this->is_enabled = !plugin_isdisabled($id);
    }

    function install_directory() {
        return DOKU_PLUGIN.plugin_directory($this->id).'/';
    }

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
            $types = array_map(function($a){return ucfirst($a['type']);}, $components);
            $types = array_unique($types);
            $this->type = implode(', ', $types);
            return $this->type;
        }
        return false;
    }
}
