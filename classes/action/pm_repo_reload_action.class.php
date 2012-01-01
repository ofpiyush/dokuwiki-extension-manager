<?php
/**
 * Refresh repo database action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_repo_reload_action extends pm_base_action {
    function act() {
        $repo = new pm_repository_lib($this->manager);
        $repo->reload();
        $this->refresh($this->manager->tab);
    }
}
