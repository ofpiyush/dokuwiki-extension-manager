<?php
/**
 * Detailed info object for a single __installed__ template
 * it also define capabilities like 'can_enable'
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_template_single_lib extends pm_base_single_lib {

    function __construct(helper_plugin_extension $helper,$id,$is_template) {
        parent::__construct($helper,$id,$is_template);

        global $conf;
        $this->is_enabled = ($id == $conf['template']);
    }

    function install_directory() {
        return DOKU_TPLLIB.$this->id.'/';
    }

    function can_select() {
        return (!$this->is_protected);
    }

    function can_enable() {
        return (!$this->is_protected && !$this->is_enabled);
    }

    function default_type() {
        return 'Template';
    }

}
