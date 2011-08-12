<?php
class pm_info_action extends pm_base_action {
    function act() {
        if(!empty($this->m->plugin)) {
            $tab = !empty($_REQUEST['template'])? 'template' : 'plugin';
            $this->m->showinfo = array_pop($this->plugin);
            $this->refresh($tab,array('info'=>$this->m->showinfo),'pminfoed__'.$this->m->showinfo);
        }
    }
}
