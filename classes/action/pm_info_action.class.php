<?php
class pm_info_action extends pm_base_action {
    function act() {
        if(!empty($this->manager->plugin)) {
            $this->manager->showinfo = array_pop($this->plugin);
            $info = array('info'=>$this->manager->showinfo);
            if(!empty($_REQUEST['type']))
                $info['type'] = $_REQUEST['type'];
            if(!empty($_REQUEST['term']))
                $info['term'] = $_REQUEST['term'];
            $this->refresh($this->manager->tab,$info,'pminfoed__'.$this->manager->showinfo);
        }
    }
}
