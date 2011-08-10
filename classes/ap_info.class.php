<?php
/**
 * Info class
 * Not really required but kept here to enable button support again in future
 */
class ap_info extends ap_plugin {
    function process() {
        if(!empty($this->plugin)) {
            $tab = !empty($_REQUEST['template'])? 'template' : 'plugin';
            $this->showinfo = array_pop($this->plugin);
            $this->refresh($tab,array('info'=>$this->showinfo));
        }
        parent::process();
    }
}
