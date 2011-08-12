<?php
class template_single extends base_single {
    protected function setup() {
        global $conf;
        $this->is_bundled = ($this->id == 'default');
    }
    
}
