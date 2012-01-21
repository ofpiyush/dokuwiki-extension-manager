<?php
/**
 * Info action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_info_action extends pm_base_action {
    function act() {
        if(!empty($this->selection)) {
            list($repokey,$folder) = explode('/',array_pop($this->selection),2);

            if (substr($repokey,0,1) == '-') {
                $repokey = substr($repokey,1);
                $extra = array('info'=>'');
            } else {
                $extra = array('info'=>$repokey);
            }
            // preserve search query
            if(!empty($_REQUEST['q']))
                $extra['q'] = $_REQUEST['q'];

            $this->refresh($this->manager->tab,$extra,'extensionplugin__'.str_replace(':','_',$repokey));
        }
    }
}
