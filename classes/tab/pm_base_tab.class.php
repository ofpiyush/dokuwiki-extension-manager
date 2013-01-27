<?php
/**
 * Base class for rendering the tab results
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
abstract class pm_base_tab {

    /** @var action_plugin_extension|admin_plugin_extension $manager*/
    public $manager = null;

    /** @var helper_plugin_extension $helper*/
    public $helper = null;

    /** @var array $lang */
    public $lang = array();

    /** @var array $downloaded */
    public $downloaded = array();

    /** @var bool $updates_available */
    protected $updates_available = false;

    /**
     * Constructor
     *
     * Initializes the member variables based on the passed Plugin object
     *
     * @param admin_plugin_extension|action_plugin_extension $manager
     */
    function __construct($manager) {
        $this->manager = $manager;
        $this->helper  = $manager->hlp;
        $this->check_writable();
    }

    abstract function process();

    abstract function html();

    abstract function check_writable();

    function html_menu() {
        global $ID;

        $tabs_array = array(
            'plugin'   => $this->manager->getLang('tab_plugin'),
            'template' => $this->manager->getLang('tab_template'),
            'search'   => $this->manager->getLang('tab_search')
        );
        $selected   = array_key_exists($this->manager->tab, $tabs_array) ? $this->manager->tab : 'plugin';

        ptln('<h3 class="a11y">Extension navigation</h3>');
        ptln('<ul class="tabs">');
        foreach($tabs_array as $tab => $text) {
            if($tab == $selected) {
                echo '<li><strong>'.$text;
                $this->html_updates_available();
                echo '</strong></li>';
            } else {
                ptln('<li><a href="'.wl($ID, array('do' => 'admin', 'page' => 'extension', 'tab' => $tab)).'">'.$text.'</a></li>');
            }
        }
        ptln('</ul>');
    }

    protected function html_download_disabled() {
        if($this->manager->getConf('allow_download')) return;

        echo '<div class="clearer"></div>';
        msg($this->manager->getLang('download_disabled'), -1);
    }

    protected function html_updates_available() {
        if(!$this->updates_available) return;

        echo '<div class="msg notify">';
        echo sprintf($this->manager->getLang('updates_available'), $this->updates_available);
        echo '</div>';
    }

    protected function html_urldownload() {
        if(!$this->manager->getConf('allow_download')) return;

        $url_form = new Doku_Form('extension__manager_urldownload');
        $url_form->addHidden('page', 'extension');
        $url_form->addHidden('fn', 'download');
        $url_form->startFieldset($this->manager->getLang('urldownload_text'));
        $url_form->addElement(form_makeTextField('url', '', $this->manager->getLang('url'), 'dw__url'));
        $url_form->addElement(form_makeButton('submit', 'admin', $this->manager->getLang('btn_download')));
        $url_form->endFieldset();
        $url_form->printForm();
    }

    /**
     * Render search box, $type is used to limit search to only plugins or templates
     */
    protected function html_search($type, $value = '') {
        global $lang;

        $search_form = new Doku_Form(array('id' => 'extension__manager_search', 'class' => 'search'));

        if(!$type) {
            $search_form->startFieldset($lang['btn_search']);
        }
        $search_form->addHidden('page', 'extension');
        $search_form->addHidden('tab', 'search');
        $search_form->addElement(form_makeTextField('q', hsc($value), '', 'extensionplugin__searchtext'));
        $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search']));
        $search_form->addHidden('type', $type);
        if(!$type) {
            $search_form->addElement('<p>');
            $search_form->addElement($this->manager->getLang('search_intro'));
            $search_form->addElement('</p>');
            $search_form->endFieldset();
        }
        $search_form->printForm();
    }

    function html_taglink($tag, $class = '') {
        global $ID;

        $params = array(
            'do'   => 'admin',
            'page' => 'extension',
            'tab'  => 'search',
            'q'    => 'tag:'.$tag,
        );
        $url    = wl($ID, $params);
        return '<a href="'.$url.'" class="taglink '.$class.'" title="'.$this->manager->getLang('tag_hint').'">'.$tag.'</a> ';
    }

    function reload_repo_link() {
        global $ID;

        $params = array(
            'do'     => 'admin',
            'page'   => 'extension',
            'tab'    => $this->manager->tab,
            'fn'     => 'repo_reload',
            'sectok' => getSecurityToken()
        );

        echo html_btn('reload', $ID, '', $params, 'post', '', $this->manager->getLang('btn_reload'));
    }

    protected function _info_list($index) {
        return $this->helper->info->get($index, $this->manager->tab);
    }

    //sorting based on name
    protected function _sort($a, $b) {
        return strnatcasecmp($a->displayname, $b->displayname);
    }
}
