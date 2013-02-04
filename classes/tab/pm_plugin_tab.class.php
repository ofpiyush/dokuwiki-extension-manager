<?php
/**
 * Plugin tab render class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
class pm_plugin_tab extends pm_base_tab {
    var $plugins;
    var $protected_plugins;
    var $actions_list;

    function process() {
        $this->actions_list      = array(
            'enable'    => $this->manager->getLang('btn_enable'),
            'disable'   => $this->manager->getLang('btn_disable'),
            'delete'    => $this->manager->getLang('btn_delete'),
            'update'    => $this->manager->getLang('btn_update'),
            'reinstall' => $this->manager->getLang('btn_reinstall'),
        );
        $this->possible_errors   = array(
            'needed_by'     => $this->manager->getLang('needed_by'),
            'not_writable'  => $this->manager->getLang('not_writable'),
            'bundled'       => $this->manager->getLang('bundled_source'),
            'gitmanaged'    => $this->manager->getLang('gitmanaged'),
            'missing_dlurl' => $this->manager->getLang('no_url'),
        );
        $list                    = array_map(array($this, '_info_list'), $this->helper->plugin_list);
        $this->updates_available = count(array_filter($list, create_function('$info', 'return $info->update_available;')));
        usort($list, array($this, '_sort'));
        $protected                           = array_filter($list, array($this, '_is_protected'));
        $notprotected                        = array_diff_key($list, $protected);
        $this->plugins['enabled']            = array_filter($notprotected, array($this, '_is_enabled'));
        $this->plugins['disabled']           = array_diff_key($notprotected, $this->plugins['enabled']);
        $this->protected_plugins['enabled']  = array_filter($protected, array($this, '_is_enabled'));
        $this->protected_plugins['disabled'] = array_diff_key($protected, $this->protected_plugins['enabled']);
    }

    /**
     * Filter functions
     */
    function _is_protected($info) {
        return $info->is_protected;
    }

    function _is_enabled($info) {
        return $info->is_enabled;
    }

    /**
     * Plugin tab rendering
     */
    function html() {
        $this->html_search($this->manager->tab);
        $this->html_menu();
        ptln('<div class="panelHeader">');
        $summary = sprintf($this->manager->getLang('summary_plugin'), count($this->helper->plugin_list), count($this->plugins['enabled']) + count($this->protected_plugins['enabled']));
        ptln('<p>'.$summary.'</p>');
        $this->html_download_disabled();
        ptln('<div class="clearer"></div></div><!-- panelHeader -->');

        $this->html_disable_all_button();
        ptln('<div class="panelContent">');
        $this->html_extensionlist();
        ptln('</div><!-- panelContent -->');
    }

    function html_disable_all_button() {
        global $ID;

        if (is_array($this->plugins) && count($this->plugins)) {
            $params = array(
                'do'     => 'admin',
                'page'   => 'extension',
                'tab'    => 'plugin',
                'fn'     => 'disable_all',
                'sectok' => getSecurityToken()
            );

            echo html_btn('disable_all', $ID, '', $params, 'post', '', $this->manager->getLang('btn_disable_all'));
        }
    }

    function html_extensionlist() {
        // managable plugins
        if(is_array($this->plugins) && count($this->plugins)) {
            $list = new pm_plugins_list_lib($this->manager, 'extensionplugin__pluginslist', $this->actions_list, $this->possible_errors);
            $list->add_header('installed_plugins', $this->manager->getLang('header_plugin_installed'));
            $list->start_form();
            foreach($this->plugins as $status => $plugins) {
                foreach($plugins as $info) {
                    $list->add_row($info);
                }
            }
            $list->end_form(array('enable', 'disable', 'delete', 'update'));
            $list->render();
        }
        // protected plugins
        if(is_array($this->protected_plugins) && count($this->protected_plugins)) {
            $protected_list = new pm_plugins_list_lib($this->manager, 'extensionplugin__pluginsprotected', array(), $this->possible_errors);
            $protected_list->add_header('protected_plugins', $this->manager->getLang('header_plugin_protected'));
            $protected_list->add_p($this->manager->getLang('text_plugin_protected'));
            $protected_list->start_form();
            foreach($this->protected_plugins as $status => $plugins)
                foreach($plugins as $info) {
                    $protected_list->add_row($info);
                }
            $protected_list->end_form(array());
            $protected_list->render();
        }
    }

    function check_writable() {
        if(!$this->helper->pluginfolder_writable) {
            msg($this->manager->getLang('not_writable')." ".DOKU_PLUGIN, -1);
        }
    }
}
