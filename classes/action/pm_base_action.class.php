<?php
/**
 * Base action class, common functions for all child actions
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
abstract class pm_base_action {

    protected $selection = null;
    protected $manager   = null;
    protected $helper    = null;

    final public function __construct(admin_plugin_extension $manager) {
        $this->selection = $manager->selection;
        $this->helper    = $manager->hlp;
        $this->manager   = $manager;
        $this->act();
    }

    /**
     * takes the requested action. to be declared by the child classes
     */
    abstract protected function act();

    /**
     *  Refresh plugin list
     */
    protected function refresh($tab = "plugin", $extra = false, $anchor = '') {
        global $config_cascade;

        // expire dokuwiki caches
        // touching local.php expires wiki page, JS and CSS caches
        @touch(reset($config_cascade['main']['local']));

        global $ID;
        $params = array('do' => 'admin', 'page' => 'extension', 'tab' => $tab);
        if(!empty($extra)) $params = array_merge($params, $extra);
        if(!empty($anchor)) $anchor = "#".$anchor;
        send_redirect(wl($ID, $params, true, '&').$anchor);
    }

    /**
     * delete, with recursive sub-directory support
     */
    protected function dir_delete($path) {
        if(!is_string($path) || $path == "") return false;

        if(is_dir($path) && !is_link($path)) {
            if(!$dh = @opendir($path)) return false;

            while ($f = readdir($dh)) {
                if($f == '..' || $f == '.') continue;
                $this->dir_delete("$path/$f");
            }

            closedir($dh);
            return @rmdir($path);
        } else {
            return @unlink($path);
        }

        return false;
    }

    /**
     * output message to user & log to file
     */
    protected function report($lvl, $info, $langkey) {
        $arg_list = func_get_args();
        $args     = array_merge( array('<em>'.$info->id.'</em>'), array_slice($arg_list, 3));

        $key      = 'msg_'.($info->is_template ? 'tpl_':'').$langkey;
        $message  = vsprintf($this->manager->getLang($key), $args);

        // repokey used to avoid repetition of urls in log file when action download (url)
        $this->helper->log->trace(str_replace('template:', '', $info->repokey), strip_tags($message));
        msg($message, $lvl);
    }

}
