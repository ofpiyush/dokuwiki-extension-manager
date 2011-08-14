<?php
class pm_reinstall_action extends pm_update_action {

    function successtemp($base) {
        msg(sprintf($this->manager->getLang('tempreinstalled'),hsc($base)),1);
    }
    function successplug($base) {
        msg(sprintf($this->manager->getLang('reinstalled'),hsc($base)),1);
    }
    function fail($plugin,$extra) {
        msg("<strong>".hsc($plugin).":</strong> ".$this->manager->getLang('reinstall_error')."<br />".$extra,-1);
    }
}
