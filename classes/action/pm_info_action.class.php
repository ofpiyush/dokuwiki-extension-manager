<?php
class pm_info_action extends pm_base_action {
    function act() {
        if(!empty($this->m->plugin)) {
            $this->m->showinfo = array_pop($this->plugin);
            $info = array('info'=>$this->m->showinfo);
            if(!empty($_REQUEST['type']))
                $info['type'] = $_REQUEST['type'];
            if(!empty($_REQUEST['term']))
                $info['term'] = $_REQUEST['term'];
            $this->refresh($this->m->tab,$info,'pminfoed__'.$this->m->showinfo);
        }
    }
}
