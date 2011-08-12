<?php
class pm_search_single_lib extends pm_base_single_lib {

    function can_select() {
        return !empty($this->downloadurl);
    }
    function can_download() {
        if(empty($this->downloadurl)) return false;
        if(!$this->is_writable) return false;
        if($this->is_template) return false;
        return true;
    }
    function can_disdown() {
        if(empty($this->downloadurl)) return false;
        if(!$this->is_writable) return false;
        return true;
    }
    protected function get_version() {
        return $this->repo['lastupdate'];
    }
}
