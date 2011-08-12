<?php
class pm_search_single_lib extends pm_base_single_lib {
    protected function setup() {
    }
    function can_download() {
        if(empty($this->downloadurl)) return false;
        if(!is_writable($this->basepath)) return false;
        return true;
    }
    protected function get_version() {
        return $this->repo['lastupdate'];
    }
}
