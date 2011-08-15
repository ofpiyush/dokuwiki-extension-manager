<?php
class pm_repo_reload_action extends pm_base_action {
    function act() {
        $repo = new pm_repository_lib($this->manager);
        $repo->reload();
        $this->refresh($this->manager->tab);
    }
}
