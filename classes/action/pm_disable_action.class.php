<?php
/**
 * Disable action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_disable_action extends pm_base_action {

    var $result = array();

    function act() {
        if(is_array($this->selection)) {
            array_walk($this->selection,array($this,'disable'));
        }
        $this->refresh($this->manager->tab);
    }

    function disable($cmdkey) {
        $info = $this->manager->info->get($cmdkey);
        if(!$info->can_disable()) return false;
        if($info->is_template) return false;

        if (plugin_disable($info->id)) {
            $this->report(1,$info,'disabled');
            return true;
        } else {
            $this->report(-1,$info,'notdisabled');
            return false;
        }
    }

}

