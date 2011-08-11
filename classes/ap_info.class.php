<?php
/**
 * Info class
 * Not really required but kept here to enable button support again in future
 */
class ap_info extends ap_plugin {
    function process() {
        // sanity check
        if(!empty($this->plugin)) {
            $this->showinfo = array_pop($this->plugin);
            $this->refresh('plugin',array('info'=>$this->showinfo));
        }
        parent::process();
    }
}
