<?php
/**
 * Detailed info object for a single __installed__ template
 * it also define capabilities like 'can_enable'
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_template_single_lib extends pm_base_single_lib {

    function can_select() {
        return (!$this->is_protected);
    }
}
