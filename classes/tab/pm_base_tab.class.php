<?php
/**
 * Base render class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

abstract class pm_base_tab {

    var $manager = NULL;
    var $lang = array();
    var $downloaded = array();

    function __construct(admin_plugin_extension $manager) {
        $this->manager = $manager;
        $this->check_writable();
    }

    abstract function process();

    abstract function html();

    abstract function check_writable();

    function html_menu() {
        global $ID;

        $tabs_array = array(
            'plugin' => $this->manager->getLang('tab_plugin'),
            'template' =>$this->manager->getLang('tab_template'),
            'search' =>$this->manager->getLang('tab_search')
        );
        $selected = array_key_exists($this->manager->tab,$tabs_array)? $this->manager->tab : 'plugin' ;

	    ptln('<ul class="tabs">');
	    foreach($tabs_array as $tab =>$text) {
            if ($tab == $selected) {
                ptln('<li><strong>'.$text.'</strong></li>');
            } else {
                ptln('<li><a '.$class.' href="'.wl($ID,array('do'=>'admin','page'=>'extension','tab'=>$tab)).'">'.$text.'</a></li>');
            }
	    }
	    ptln('</ul>');
    }

    protected function html_download_disabled() {
        if ($this->manager->getConf('allow_download')) return;

        echo '<div class="message notify">';
        echo $this->manager->getLang('download_disabled');
        echo '</div>';
    }

    protected function html_urldownload() {
        if (!$this->manager->getConf('allow_download')) return;

        $url_form = new Doku_Form('extension__manager_urldownload');
        $url_form->startFieldset($this->manager->getLang('urldownload_text'));
        $url_form->addElement(form_makeTextField('url','',$this->manager->getLang('urldownload_text'),'dw__url'));
        $url_form->addHidden('page','extension');
        $url_form->addHidden('fn','download');
        $url_form->addElement(form_makeButton('submit', 'admin', $this->manager->getLang('btn_download') )); // change to img button
        $url_form->endFieldset();
        $url_form->printForm();
    }

    protected function html_search($header,$type = null,$value = '') {
        global $lang;

        ptln('<h2>'.$header.'</h2>');
        ptln('Duis rutrum lacinia sem, eu ultrices libero fringilla eget. Nam pretium tristique ligula, sit amet consequat lacus ultricies at. Mauris at ligula mi. Vivamus interdum aliquam risus vitae rutrum. Quisque faucibus sem in nibh aliquam sagittis ultrices sapien pharetra. Ut ac felis massa, a suscipit ligula. Curabitur sed ligula lorem. Donec neque est, commodo nec interdum vitae, dictum in nisl. Integer mattis, magna fringilla rhoncus scelerisque');
        $search_form = new Doku_Form('extension__manager_search');
        $search_form->startFieldset($lang['btn_search']);
        $search_form->addElement(form_makeTextField('term',hsc($value),$lang['btn_search'],'extensionplugin__searchtext'));
        $search_form->addHidden('page','extension');
        $search_form->addHidden('tab','search');
        $search_form->addHidden('fn','search');
        // TODO maybe remove this listbox type selection in favor for a search query parser?
        $type_default = "";
        if(!empty($this->extra['type'])) $type_default = $this->extra['type'];
        if($type !== null) {
            if(is_array($type) && count($type)) {
                $search_form->addElement(form_makeMenuField('type',$type,$type_default,$this->manager->getLang('type')));
            } else {
                $search_form->addHidden('type',$type);
            }
        }
        $search_form->addElement(form_makeButton('submit', 'admin', $lang['btn_search'] ));
        $search_form->endFieldset();
        $search_form->printForm();
    }

    function reload_repo_link() {
        global $ID;

        $params = array('do'=>'admin',
                        'page'=>'extension',
                        'tab'=>$this->manager->tab,
                        'fn'=>'repo_reload',
                        'sectok'=>getSecurityToken()
                        );

        echo '<div class="repo_reload">'.sprintf($this->manager->getLang('repo_reload'),2); // TODO move hardcoded value
        echo html_btn('reload', $ID, '', $params, 'post', '', 'Reload');
        echo '</div>';
    }

    function _info_list($index,$type ="plugin") {
        return $this->manager->info->get($index,$type);
    }

    //sorting based on name
    protected function _sort($a,$b) {
        return strnatcasecmp($a->id,$b->id);
    }
}
