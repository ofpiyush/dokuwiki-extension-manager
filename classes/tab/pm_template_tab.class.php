<?php
/**
 * Template tab render class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_template_tab extends pm_base_tab {

    var $templates = array();
    var $enabled = array();
    var $tpl_default = array();
    var $actions_list = array();

    function process() {
        global $conf;
        $list = $this->manager->template_list;
        $this->templates['enabled'][0] = $this->_info_list($conf['template']);
        $disabled = array_diff($list,array($conf['template']));
        $this->templates['disabled'] = array_map(array($this,'_info_list'),$disabled); 
        usort($this->templates['disabled'],array($this,'_sort'));
        $this->actions_list = array(
            'enable'=>$this->manager->getLang('enable'),
            'delete'=>$this->manager->getLang('btn_delete'),
            'update'=>$this->manager->getLang('btn_update'),
            'reinstall' =>$this->manager->getLang('btn_reinstall'),
        );
        $this->possible_errors = array(
            'missing_dependency' => $this->manager->getLang('depends'),
            'not_writable' => $this->manager->getLang('not_writable'),
            'bundled' => $this->manager->getLang('bundled_source'),
            'gitmanaged' => $this->manager->getLang('gitmanaged'),
            'missing_dlurl' => $this->manager->getLang('no_url'),
        );
    }

    /**
     * Template tab rendering
     */
    function html() {
        $this->html_menu();
        ptln('<div class="panelHeader">');
        $summary = sprintf($this->manager->getLang('summary_template'),count($this->manager->template_list));
	    ptln('<h3>'.$summary.'</h3>');
        $this->html_download_disabled();
        ptln('</div><!-- panelHeader -->');

        ptln('<div class="panelContent">');
        $this->html_extensionlist();
        ptln('</div><!-- panelContent -->');
    }

    function html_extensionlist() {
        $list = new pm_plugins_list_lib($this->manager,'extensionplugin__templateslist',$this->actions_list,$this->possible_errors,'template');
        $list->add_header('installed_templates',$this->manager->getLang('header_template_installed'));
        $list->start_form();
        if(!empty($this->templates)) {
            foreach($this->templates as $status => $templates) {
                foreach ($templates as $template) {
                    $list->add_row($template);
                    if($status == "enabled") $list->rowadded = false;
                }
            }
        }
        $list->end_form(array('update','delete','reinstall'));
        $list->render();
    }

    function check_writable() {
        if(!$this->manager->templatefolder_writable) {
            msg($this->manager->getLang('not_writable')." ".DOKU_TPLLIB,-1);
        }
    }

}
