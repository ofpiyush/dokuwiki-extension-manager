<?php
/**
 * Plugin Manager plugins list
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_plugins_list_lib {

    var $rowadded = false;
    protected $form = null;
    protected $actions = array();
    protected $possible_errors = array();
    protected $type = "plugin";
    protected $form_id = null;
    protected $manager = null;
    protected $columns = array();
    protected $intable = false;
    protected $actions_shown = array();

    /**
     * Plugins list constructor
     * Starts the form, table and sets up actions available to the user
     */
    function __construct(admin_plugin_extension $manager,$form_id,$actions = array(),$possible_errors=array(),$type ="plugin") {
        $this->manager = $manager;
        $this->type = $type;
        $this->possible_errors = $possible_errors;
        $this->form_id = $form_id;
        $this->actions = array_merge($this->actions,$actions);
        $this->form = '<div class="common">';
    }

    function start_form() {
        $this->form .= '<form id="'.$this->form_id.'" accept-charset="utf-8" method="post" action="">';
        $hidden = array(
            'do'=>'admin',
            'page'=>'extension',
            'tab' => $this->manager->tab,
            'sectok'=>getSecurityToken()
        );
        // preserve search query when pressing info action
        if($this->manager->tab == "search" ) {
            if(!empty($this->manager->handler->query)) {
                $hidden['q'] = $this->manager->handler->query;
            }
        }
        $this->add_hidden($hidden);
        $this->form .= '<table>';
        $this->intable = true;
    }
    /**
     * Build single row of plugin table
     * @param array  $info     a single plugin from repo cache
     * @param string $actions  html for what goes into the action column
     * @param array  $checkbox the optional parameters to be passed in for the checkbox (use-case disabling downloads)
     */
    function add_row($info) {
        if($this->intable) {
            $this->rowadded = true;
            $this->start_row($info,$this->make_class($info));
            $this->populate_column('selection',$this->make_checkbox($info,$checkbox));
            $this->populate_column('legend',$this->make_legend($info));
            $this->populate_column('version',$this->make_version($info));
            if($this->type == 'template') {
                $this->populate_column('screenshot',$this->make_screenshot($info));
            }
            $this->populate_column('actions',$this->make_actions($info));
            $this->end_row();
        }
    }

    function add_header($id,$header,$level=2) {
        $this->form .='<h'.$level.' id="'.$id.'">'.hsc($header).'</h'.$level.'>';
    }

    function add_p($data) {
        $this->form .= '<p>'.$data.'</p>';
    }

    function add_hidden(array $array) {
        $this->form .= '<div class="no">';  // TODO why use div here, compare with inc/form.php
        foreach ($array as $key => $value) {
            $this->form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }        
        $this->form .= '</div>';
    }

    /**
     * Add closing tags
     */
    function end_form($actions = array()) {
        if($this->intable) $this->form .= '</table>';
        $cmdButtons = '';
        if($this->rowadded) {
            $actions_shown = array_filter($this->actions_shown);
            if(!empty($actions_shown)) {
                $cmdButtons .= '<div class="checks"><span class="checkall">['.$this->manager->getLang('select_all').']</span>'.
                               '  <span class="checknone">['.$this->manager->getLang('select_none').']</span></div>';
            }
            $cmdButtons .= '<div class="bottom">';
            foreach($this->actions as $value => $text) {
                if(!in_array($value,$actions) || empty($actions_shown[$value])) continue;
                $cmdButtons .= '<input class="button" name="fn['.$value.']" type="submit" value="'.hsc($text).'" />';
            }
            $cmdButtons .= '</div>';
        }
        $this->form .= $cmdButtons;
        $this->form .= '</form>';
        $this->form .= '</div>';
    }
    function render() {
        echo $this->form;
    }
    private function start_row($info,$class) {
        $this->form .= '<tr id="extensionplugin__'.hsc($info->html_id).'" class="'.$class.'">';
    }
    private function populate_column($class,$html) {
        $this->form .= '<td class="'.$class.'">'.$html.'</td>';
    }
    private function end_row() {
        $this->form .= '</tr>'.DOKU_LF;
    }

    /**
     * Generate documentation/title url for a single plugin
     */
    function make_homepagelink($info) {
        if(!empty($info->dokulink)) {
            $info->url = "http://www.dokuwiki.org/".$info->dokulink;
            return $this->make_link($info,"interwiki iw_doku");
        }

        if(!empty($info->url)) {
            if(preg_match('|^http(s)?://(www.)?dokuwiki.org/(.*)?$|i', $info->url))
                return $this->make_link($info,"interwiki iw_doku");
            else
                return $this->make_link($info,"urlextern");
        }
        return '';
    }

    function make_link($info, $class) {
        $linktext = $this->manager->getLang('homepage_link');
        return '<a href="'.hsc($info->url).'" title="'.hsc($info->url).'" class ="'.$class.'">'.$linktext.'</a>';
    }

    function make_class($info) {
        $class = ($info->is_template) ? 'template' : 'plugin';
        if($info->is_installed) {
            $class.=' installed';
            $class.= ($info->is_enabled) ? ' enabled':' disabled';
        }
        if(!$info->can_select()) $class.= ' notselect';
        if($info->is_protected)
            $class.=  ' protected';
        if($info->showinfo()) $class.= ' showinfo';
        return $class;
    }

    function make_author($info) {
        if(!empty($info->author)) {
            if(!empty($info->email)) {
                return '<a href="mailto:'.hsc($info->email).'">'.hsc($info->author).'</a>';
            }
            return hsc($info->author);
        }
        return "<em>".$this->manager->getLang('unknown')."</em>";
    }

    function make_version($info) {
        $return .= '<dl>';
        if ($info->is_installed) {
            if ($info->date) {
                $return .= '<dt>'.$this->manager->getLang('installed_version').'</dt>';
                $return .= '<dd>';
                $return .= hsc($info->date);
                $return .= '</dd>';
            } else {
                $return .= '<dt>'.$this->manager->getLang('install_date').'</dt>';
                $return .= '<dd>';
                $return .= ($info->install_date ? hsc($info->install_date) : $this->manager->getLang('unknown'));
                $return .= '</dd>';
            }
        }
        if (!$info->is_installed || $info->update_available) {
            $return .= '<dt>'.$this->manager->getLang('available_version').'</dt>';
            $return .= '<dd>';
            $return .= ($info->lastupdate ? hsc($info->lastupdate) : $this->manager->getLang('unknown'));
            $return .= '</dd>';
        }
        $return .= '</dl>';
        return $return;
    }

    function make_screenshot($info) {
        $return = '';
        if(!empty($info->screenshoturl)) {
            if($info->screenshoturl[0] == ':')
                $info->screenshoturl = 'http://www.dokuwiki.org/_media/'.$info->screenshoturl;
            $return .= '<a title="'.hsc($info->displayname).'" href="'.$info->screenshoturl.'">'.
                    '<img alt="'.hsc($info->displayname).'" width="80" src="'.hsc($info->screenshoturl).'" />'.
                    '</a>';
        }
        return $return;
    }

    /**
     * Extension main description
     */
    function make_legend($info) {
        global $lang;

        if ($info->is_template) {
            $return .= '<img alt="" width="48" src="lib/plugins/extension/images/template.png" />';
        } else {
            $return .= '<img alt="" width="48" src="lib/plugins/extension/images/plugin.png" />';
        }
        $return .= '<label for="'.$this->form_id.'_'.hsc($info->html_id).'">'.hsc($info->displayname).'</label>';
        $return .= ' by '.$this->make_author($info);

        if ($info->popularity && !$info->is_bundled) {
            list($progressCount,$progressWidth) = explode(',',$info->popularity,2);
            $return .= '<div class="progress" title="'.$progressCount.'"><div style="width: '.$progressWidth.'%;"><span>'.$progressCount.'</span></div></div>';
        }
        $compatible = $info->compatible_status($this->manager->dokuwiki_version['date']);
        if ($compatible) {
            $return .= '<div class="status '.$compatible.'" title="'.$this->manager->getLang('status_'.$compatible).'">'.$this->manager->dokuwiki_version['name'].'</div>';
        }

        $return .= '<p>';
        if(!empty($info->description)) {
            $return .=  hsc($info->description).' ';
        }
        $return .= '</p>';
        $return .= '<div class="clearer"></div>';

        $return .= $this->make_homepagelink($info);
        if ($info->bugtracker) {
            $return .= ' &bull; <a href="'.hsc($info->bugtracker).'" title="'.hsc($info->bugtracker).'" class ="urlextern">'.$this->manager->getLang('bugs_features').'</a>';
        }
        if(!empty($info->tags) && is_array($info->tags['tag'])) {
            $return .= ' &bull; '.$this->manager->getLang('tags').' ';
            foreach ($info->tags['tag'] as $tag) {
                $return .= $this->manager->handler->html_taglink($tag);
            }
        }
        $return .= $this->make_info($info);
        $return .= $this->make_noticearea($info);
        return $return;
    }

    /**
     * Notice area
     */
    function make_noticearea($info) {
        if($info->wrong_folder()) {
            $return .= '<div class="message error">'.
                            sprintf($this->manager->getLang('wrong_folder'),hsc($info->id),hsc($info->base)).
                        '</div>';
        }
        if(!empty($info->securityissue)) {
            $return .= '<div class="message error">'.
                            sprintf($this->manager->getLang('security_issue'),hsc($info->securityissue)).
                        '</div>';
        }
        if(!empty($info->securitywarning)) {
            $return .= '<div class="message notify">'.
                            sprintf($this->manager->getLang('security_warning'),hsc($info->securitywarning)).
                        '</div>';
        }
        if($info->update_available) {
            $return .=  '<div class="message notify">'.
                            sprintf($this->manager->getLang('update_available'),hsc($info->lastupdate)).
                        '</div>';
        }
        if($info->url_changed()) {
            $return .=  '<div class="message notify">'.
                            sprintf($this->manager->getLang('url_change'),hsc($info->repo['downloadurl']),hsc($info->log['downloadurl'])).
                        '</div>';
        }
        return $return;
    }

    /**
     * Plugin/template details
     */
    function make_info($info) {
        if(!$info->showinfo()) {
            return $this->make_action('info',$info,$this->manager->getLang('btn_info'));
        }
        $default = $this->manager->getLang('unknown');
        $return .= '<dl class="details">';

        if (!$info->is_bundled) {
            $return .= '<dt>'.$this->manager->getLang('source').'</dt>';
            $return .= '<dd>';
            $return .= (!empty($info->downloadurl) ? hsc($info->downloadurl) : $default);
            $return .= '</dd>';
        }

// TODO installed, updated

        if(!empty($info->install_date)) {
            $return .= '<dt>'.$this->manager->getLang('installed').'</dt>';
            $return .= '<dd>';
            $return .= hsc($info->install_date);
            $return .= '</dd>';
        }

        $return .= '<dt>'.$this->manager->getLang('components').'</dt>';
        $return .= '<dd>';
        $return .= (!empty($info->type) ? hsc($info->type) : $default);
        $return .= '</dd>';

        if(!empty($info->compatible)) {
            $return .= '<dt>'.$this->manager->getLang('compatible').'</dt>';
            $return .= '<dd>';
            $return .= hsc(implode(', ',(array)$info->compatible['release']));
            $return .= '</dd>';
        }
        if(!empty($info->relations['depends']['id'])) {
            $return .= '<dt>'.$this->manager->getLang('depends').'</dt>';
            $return .= '<dd>';
            $return .= $this->make_linklist((array)$info->relations['depends']['id']);
            $return .= '</dd>';
        }

        if(!empty($info->relations['similar']['id'])) {
            $return .= '<dt>'.$this->manager->getLang('similar').'</dt>';
            $return .= '<dd>';
            $return .= $this->make_linklist((array)$info->relations['similar']['id']);
            $return .= '</dd>';
        }

        if(!empty($info->relations['conflicts']['id'])) {
            $return .= '<dt>'.$this->manager->getLang('conflicts').'</dt>';
            $return .= '<dd>';
            $return .= $this->make_linklist((array)$info->relations['conflicts']['id']);
            $return .= '</dd>';
        }
        $return .= '</dl>';

        if ($info->donationurl) {
            $return .= '<a href="'.hsc($info->donationurl).'" class="donate" title="'.$this->manager->getLang('donate').'"></a>';
        }
        $return .= $this->make_action('info',$info,$this->manager->getLang('btn_info'));
        return $return;
    }

    function make_linklist($links) {
        foreach ($links as $link) {
            $dokulink = hsc($link);
            if (strpos($link,'template:') !== 0) $dokulink = 'plugin:'.$dokulink;
            $return .= '<a href="http://www.dokuwiki.org/'.$dokulink.'" title="'.$dokulink.'" class="interwiki iw_doku">'.$link.'</a> ';
        }
        return $return;
    }

    function make_checkbox($info) {
        $checked =" ";
        if(!$info->can_select()) {
            $checked .= 'disabled="disabled"';
        }
        return '<input id="'.$this->form_id.'_'.hsc($info->html_id).'" type="checkbox"'.
               ' name="checked[]" value="'.$info->cmdkey.'" '.$checked.' /><br />';
    }

    function make_actions($info) {

        foreach($this->actions as $act => $text) {
            if($info->{"can_".$act}()) {
                $this->actions_shown[$act] = true;
                $return .= $this->make_action($act,$info,$text);
            }
        }

        if(!empty($this->possible_errors)) {
            foreach($this->possible_errors as $error => $text) {
                if($info->$error()) {
                    if(is_array($info->$error)) {
                        $return .= "<br />(<em>".$text." ".hsc(implode(', ',$info->$error))."</em>)";
                    } else {
                        $return .= "<br />(<em>".$text."</em>)";
                    }
                }
            }
        }
        return $return;
    }

    function make_action($action,$info,$text) {
        switch ($action) {
            case 'info':
                if ($info->showinfo()) {
                    return '<input class="button" name="fn['.$action.'][-'.$info->cmdkey.']" type="submit" value="'.$text.'" />';
                }
            case 'enable':
            case 'disable':
            case 'delete':
                return '<input class="button" name="fn['.$action.']['.$info->cmdkey.']" type="submit" value="'.$text.'" />';

            default:
                return '<input class="button" name="fn['.$action.']['.$info->cmdkey.']" type="submit" value="'.$text.'" title="'.$info->downloadurl.'" />';
        }
    }

}
