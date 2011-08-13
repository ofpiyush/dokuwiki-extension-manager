<?php
class pm_search_single_lib extends pm_base_single_lib {

    function can_select() {
        return !empty($this->downloadurl);
    }
    function can_download() {
        if($this->is_template) return false;
        return $this->can_disdown();
    }
    function can_disdown() {
        if(empty($this->downloadurl)) return false;
        if(!$this->is_writable) return false;
        if($this->has_conflicts()) return false;
        if($this->missing_dependency()) return false;
        return true;
    }
    protected function get_version() {
        return $this->repo['lastupdate'];
    }

}
