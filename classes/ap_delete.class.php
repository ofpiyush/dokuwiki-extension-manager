<?php
class ap_delete extends ap_plugin {

    function process() {
        global $plugin_protected;
        foreach($this->plugin as $plugin) {
            if(in_array($plugin,$plugin_protected)) continue;
            if (!$this->dir_delete(DOKU_PLUGIN.plugin_directory($plugin))) {
                $this->manager->error = sprintf($this->lang['error_delete'],$plugin);
            } else {
                msg(sprintf($this->lang['deleted'],$plugin));
            }
        }
        $this->refresh();
    }

    function html() {
        parent::html();

        ptln('<div class="pm_info">');
        ptln('<h2>'.$this->lang['deleting'].'</h2>');

        if ($this->manager->error) {
            ptln('<div class="error">'.str_replace("\n","<br />",$this->manager->error).'</div>');
        } else {
            ptln('<p>'.sprintf($this->lang['deleted'],$this->plugin).'</p>');
        }
        ptln('</div>');
    }
}

