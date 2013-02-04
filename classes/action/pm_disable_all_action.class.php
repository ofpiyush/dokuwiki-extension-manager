<?php
/**
 * Disable all action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Håkan Sandell <sandell.hakan@gmail.com>
 */
class pm_disable_all_action extends pm_disable_action {

    protected function act() {
        if(is_array($this->helper->plugin_list)) {
            array_walk($this->helper->plugin_list, array($this, 'disable'));
        }
        $this->refresh($this->manager->tab);
    }

    private function disable($id) {
        if(plugin_disable($id)) {
            $this->report(1, $info, 'disabled');
        }
    }
}

