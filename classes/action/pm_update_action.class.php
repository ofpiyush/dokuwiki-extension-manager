<?php
/**
 * Update action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
class pm_update_action extends pm_download_action {

    /**
     * Report action failed
     */
    protected function msg_failed($info, $error) {
        $this->report(-1, $info, 'update_failed', $error);
    }

    /**
     * Report action succeeded
     */
    protected function msg_success($info) {
        $this->report(1, $info, 'update_success');
    }

    /**
     * Report action succeeded (more than one extension)
     */
    protected function msg_pkg_success($info,$components) {
        $this->report(1, $info, 'update_pkg_success',$components);
    }

}

