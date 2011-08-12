<?php
class pm_template_single_lib extends pm_base_single_lib {
    /**
     * If template is bundled
     * @var bool
     */
    var $is_bundled = false;
    /**
     * If template is protected
     * @var bool
     */
    var $is_protected = false;
    /**
     * If template is enabled
     * @var bool
     */
    var $is_enabled = false;

    function can_select() {
        return !($this->is_protected);
    }
}
