<?php
require_once(DOKU_PLUGIN."plugin/classes/ap_download.class.php");
class ap_update extends ap_download {

    var $overwrite = true;

    function down() {
        foreach($this->plugin as $plugin) {
            $this->current = null;
            $this->manager->error = null;
            if(in_array($plugin,$this->_bundled)) continue;
            $plugin_url = $this->plugin_readlog($plugin, 'url');
            if(!empty($plugin_url)) {
                if($this->download($plugin_url, $this->overwrite)) {
                    $base = $this->current['base'];
                    if($plugin['type'] == 'Template') {
                        msg(sprintf("Template %s successfully updated",$base),1);
                    } else {
                        msg(sprintf($this->lang['updated'],$base),1);
                    }
                } else {
                    msg("<strong>".$plugin.":</strong> ".$this->lang['update_none']."<br />".$this->manager->error,-1);
                }
            }
            else {
                msg("<strong>".$plugin.":</strong> ".$this->lang['update_none']."<br />"."Couldnot find manager.dat file for the plugin",-1);
            }
            
        }
    }
}

