<?php
/**
 * Download dependency action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_download_dependency_action extends pm_download_action {
    function down() {
        if(is_array($this->selection)) {
            foreach($this->selection as $plugin) {
                $info = $this->manager->info->get($plugin,'search');
                if($info->can_download_dependency()) {
                    //get $info->missing_dependency, add it to current list and use download
                }
            }
        }
    }
}
