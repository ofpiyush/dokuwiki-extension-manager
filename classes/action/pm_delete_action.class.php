<?php
/**
 * Delete action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_delete_action extends pm_base_action {

    var $result = array();

    function act() {
        if(is_array($this->selection)) {
            array_walk($this->selection,array($this,'delete'));
        }
    }

    /**
     * Delete the whole plugin/template directory
     * @param string name of the plugin or template directory to delete
     * @return bool if the directory delete was successful or not
     */
    function delete($repokey) {
        $info = $this->manager->info->get($repokey);
        if(!$info->can_delete()) return false;

        $path = $info->install_directory();
        $path = substr($path, 0, -1); // remove trailing slash
        if ($this->dir_delete($path)) {
            $list = $this->manager->tab.'_list';
            $this->manager->$list = array_diff($this->manager->$list,array($info->id));
            $this->report(1,$info,'deleted');
            return true;

        } else {
            $this->report(1,$info,'notdeleted');
            return false;
        }
    }

}
