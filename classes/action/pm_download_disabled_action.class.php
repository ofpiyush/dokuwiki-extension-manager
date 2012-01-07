<?php
/**
 * Download as disabled action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_download_disabled_action extends pm_download_action {

    /**
     * Download then disable if plugin
     */
    function download_single($info) {
        if (!$info->can_download_disabled()) return;
        $this->download($info, $this->overwrite, $info->id);

        if(isset($this->downloaded['plugin']) && is_array($this->downloaded['plugin'])) {
            array_filter($this->downloaded['plugin'],'plugin_disable');
        }
    }

}
