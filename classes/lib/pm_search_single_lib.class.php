<?php
/**
 * Detailed info object for a single unistalled extension (plugin or template repository search result)
 * it also define capabilities like 'can_download'
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_search_single_lib extends pm_base_single_lib {

    function can_select() {
        if(empty($this->downloadurl)) return false;
        if(!$this->is_writable) return false;
        if($this->is_installed) return false;
        return true;
    }

    function can_download() {
        if($this->is_template) return false;
        if($this->has_conflicts()) return false;
        if($this->missing_dependency()) return false;
        return $this->can_download_disabled();
    }

    function can_download_disabled() {
        if(empty($this->downloadurl)) return false;
        if($this->is_installed) return false;
        if($this->no_fileactions_allowed()) return false;
        return true;
    }

    function can_download_dependency() {
        return $this->missing_dependency();
    }

    protected function get_version() {
        return $this->repo['lastupdate'];
    }

    function wrong_folder() {
        return false;
    }

}
