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
        if(in_array($this->manager->tab,array('plugin','template'))) {
            $this->result[$this->manager->tab.'deleted']      = array_filter($this->selection,array($this,'delete'));
            $this->result[$this->manager->tab.'notdeleted']   = array_diff($this->selection,$this->result[$this->manager->tab.'deleted']);
            $this->show_results();
            $this->refresh($this->manager->tab);
            $list = $this->manager->tab.'_list';
            $this->manager->$list = array_diff($this->manager->$list,$this->result[$this->manager->tab.'deleted']);
        }
    }

    /**
     * Delete the whole plugin/template directory
     * @param string name of the plugin or template directory to delete
     * @return bool if the directory delete was successful or not
     */
    function delete($plugin) {
        $info = $this->manager->info->get($plugin,$this->manager->tab);
        if($info->is_template)
            $path = DOKU_TPLLIB.$plugin;
        else
            $path = DOKU_PLUGIN.plugin_directory($plugin);
        if(!$info->can_delete()) return false;
        return $this->dir_delete($path);
    }

    /**
     * say_<type><action taken> functions
     * parameters plugin name and unused key from array_walk
     */
    function say_plugindeleted($plugin,$key) {
        msg(sprintf($this->manager->getLang('deleted'),$plugin),1);
    }

    function say_pluginnotdeleted($plugin,$key) {
        msg(sprintf($this->manager->getLang('error_delete'),$plugin),-1);
    }
    function say_templatedeleted($plugin,$key) {
        msg(sprintf($this->manager->getLang('template_deleted'),$plugin),1);
    }

    function say_templatenotdeleted($plugin,$key) {
        msg(sprintf($this->manager->getLang('template_error_delete'),$plugin),-1);
    }
}
