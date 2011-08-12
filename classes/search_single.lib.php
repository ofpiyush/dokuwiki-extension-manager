<?php
class search_single extends base_single {
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
