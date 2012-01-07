<?php
/**
 * Re-install action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_reinstall_action extends pm_update_action {

    /**
     * Report action failed
     */
    function msg_failed($info, $error) {
        $this->report(-1, $info, 'reinstall_failed', $error);
    }

    /**
     * Report action succeeded
     */
    function msg_success($info) {
        $this->report(1, $info, 'reinstall_success');
    }

    /**
     * Report action succeeded (more than one extension)
     */
    function msg_pkg_success($info,$components) {
        $this->report(1, $info, 'reinstall_pkg_success',$components);
    }

}
