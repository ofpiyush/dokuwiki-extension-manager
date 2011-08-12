<?php
class pm_template_single_lib extends pm_base_single_lib {
    protected function setup() {
        global $conf;
        $this->is_bundled = ($this->id == 'default');
    }
}
