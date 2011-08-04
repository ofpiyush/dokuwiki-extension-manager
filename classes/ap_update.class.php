<?php
require_once(DOKU_PLUGIN."plugin/classes/ap_download.class.php");
class ap_update extends ap_download {

    var $overwrite = true;

    function down() {
        foreach($this->plugin as $plugin) {
            if(in_array($plugin,$this->_bundled)) continue;
            $plugin_url = $this->plugin_readlog($plugin, 'url');
            $this->download($plugin_url, $this->overwrite);
        }
        $this->result['updated'] = $this->downloaded;
        $this->result['notupdated'][] = 1;
    }

    function say_updated($plugin) {
        if(count($this->downloaded[$plugin]) == 1)
            msg(sprintf($this->lang['updated'],$plugin),1);
        elseif(count($this->downloaded[$plugin]))
            msg(sprintf($this->lang['updates']." ".join(',',$this->downloaded[$plugin])),1);
        elseif(!$this->manager->error)
            msg(sprintf($this->lang['update_none']),-1);
    }

    function say_notupdated($plugin) {
        if($this->manager->error)
            msg(sprintf($this->manager->error),-1);
        elseif(!count($this->downloaded))
            msg(sprintf($this->lang['update_none']),-1);
    }
}

