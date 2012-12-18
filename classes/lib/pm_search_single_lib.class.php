<?php
/**
 * Detailed info object for a single unistalled extension (plugin or template repository search result)
 * it also define capabilities like 'can_download'
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_search_single_lib extends pm_base_single_lib {

    function get_cmdkey() {
        return $this->repokey;
    }

    function get_install_date() {
        return false;
    }

    function can_reinstall() {
        return false;
    }

    function can_select() {
        if(empty($this->downloadurl)) return false;
        if(!$this->is_writable) return false;
        if($this->is_installed) return false;
        return true;
    }

    function can_download() {
        if($this->has_conflicts()) return false;
        if(empty($this->downloadurl)) return false;
        if($this->is_installed) return false;
        if($this->no_fileactions_allowed) return false;
        return true;
    }

    function can_download_dependency() {
        if(!$this->can_download) return false;
        return $this->missing_dependency();
    }

    function wrong_folder() {
        return false;
    }

    function url_changed() {
        return false;
    }

}
