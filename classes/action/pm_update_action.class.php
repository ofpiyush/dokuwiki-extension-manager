<?php
class pm_update_action extends pm_download_action {
    var $overwrite = true;
    function down() {
        $base_path = ($this->m->tab == "template")? DOKU_INC.'lib/tpl/' : DOKU_PLUGIN;
        foreach($this->plugin as $plugin) {
            if(in_array($plugin,$this->_bundled)) continue;
            $this->current = null;
            $this->manager->error = null;
            $info = $this->m->info->get($plugin,$this->m->tab);
            
            if(@file_exists($base_path.$plugin.'/manager.dat') || !empty($info->downloadurl)) {
                if(!empty($info->downloadurl)) {
                    if($this->download($info, $this->overwrite,'',$this->type)) {
                        $base = $this->current['base'];
                        if($this->m->tab == 'template') {
                            $this->successtemp($base);
                        } else {
                            $this->successplug($base);
                        }
                    } else {
                        $this->fail($plugin,$this->m->error);
                    }
                 } else {
                    $this->fail($plugin,$this->m->error);
                 }
            } else {
                $this->fail($plugin,$this->m->getLang('no_manager'));
            }
            
        }
    }
    function successtemp($base) {
        msg(sprintf($this->m->getLang('tempupdated'),hsc($base)),1);
    }
    function successplug($base) {
        msg(sprintf($this->m->getLang('updated'),hsc($base)),1);
    }
    function fail($plugin,$extra) {
        msg("<strong>".hsc($plugin).":</strong> ".$this->m->getLang('update_error')."<br />".$extra,-1);
    }
}

