<?php
require_once(DOKU_PLUGIN."plugin/classes/ap_download.class.php");
class ap_update extends ap_download {

    var $overwrite = true;

    function down() {
        foreach($this->plugin as $plugin) {
            if(in_array($plugin,$this->_bundled)) continue;
            $plugin_url = $this->plugin_readlog($plugin, 'url');
            if($this->download($plugin_url, $this->overwrite)) {
                $base = $this->current['base'];
                if($plugin['type'] == 'Template') {
                    $this->result['tempupdated'][]= $base;
                } else {
                    $this->result['updated'][]= $base;
                }
            } else {
                $this->result['notupdated'][] = $base;
                $this->downerrors[$base] = $this->manager->error;
            }
        }
    }

    function say_updated($plugin) {
        msg(sprintf($this->lang['downloaded'],$plugin),1);
    }

    function say_tempupdated($template) {
        msg(sprintf("Template %s successfully updated",$template),1);
    }

    function say_notupdated($plugin) {
        msg("<b>".$plugin.":</b> ".$this->lang['update_none']."<br />".$this->downerrors[$plugin],-1);
    }
}

