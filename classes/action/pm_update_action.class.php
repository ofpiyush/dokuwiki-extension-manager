<?php
/**
 * Update action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_update_action extends pm_download_action {
    var $overwrite = true;
    function down() {
        $base_path = ($this->manager->tab == "template")? DOKU_TPLLIB : DOKU_PLUGIN;
        foreach($this->selection as $plugin) {
            if(in_array($plugin,$this->manager->_bundled)) continue;
            $this->current = null;
            $this->manager->error = null;
            $info = $this->manager->info->get($plugin,$this->manager->tab);
            
            if(@file_exists($base_path.$plugin.'/manager.dat') || !empty($info->downloadurl)) {
                if(!empty($info->downloadurl)) {
                    if($info->{"can_".$this->manager->cmd}()) {
                        if($this->download($info, $this->overwrite,'',$this->manager->tab)) {
                            $base = $this->current['base'];
                            if($this->manager->tab == 'template') {
                                $this->successtemp($base);
                            } else {
                                $this->successplug($base);
                            }
                        } else {
                            $this->fail($plugin,$this->manager->error);
                        }
                    } else {
                        $this->fail($plugin,'');
                    }
                    
                 } else {
                    $this->fail($plugin,$this->manager->getLang('no_url'));
                 }
            } else {
                $this->fail($plugin,$this->manager->getLang('no_manager'));
            }
            
        }
    }
    function successtemp($base) {
        msg(sprintf($this->manager->getLang('tempupdated'),hsc($base)),1);
    }
    function successplug($base) {
        msg(sprintf($this->manager->getLang('updated'),hsc($base)),1);
    }
    function fail($plugin,$extra) {
        msg("<strong>".hsc($plugin).":</strong> ".$this->manager->getLang('update_error')."<br />".$extra,-1);
    }
}

