<?php
/**
 * Plugin Manager plugins list
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_plugins_list_lib {

    protected $form = null;
    protected $actions = array();
    protected $possible_errors = array();
    protected $type = "plugin";
    protected $form_id = null;
    protected $manager = null;
    protected $helper = null;
    protected $columns = array();
    protected $actions_shown = array();
    protected $showinfo = false;

    /**
     * Plugins list constructor
     * Starts the form, table and sets up actions available to the user
     */
    function __construct($manager, $form_id, $actions = array(), $possible_errors = array(), $type = "plugin") {
        $this->manager         = $manager;
        $this->helper          = $manager->hlp;
        $this->type            = $type;
        $this->possible_errors = $possible_errors;
        $this->form_id         = $form_id;
        $this->actions         = array_merge($this->actions, $actions);
        $this->form            = '<div class="common">';
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
        $this->form .= '<ul class="extensionList">';
    }
    /**
     * Build single row of plugin table
     * @param array  $info     a single plugin from repo cache
     * @param string $actions  html for what goes into the action column
     * @param array  $checkbox the optional parameters to be passed in for the checkbox (use-case disabling downloads)
     */
    function add_row($info) {
        $this->showinfo = ($this->manager->showinfo == $info->repokey);
        $this->start_row($info, $this->make_class($info));
        $this->populate_column('legend', $this->make_legend($info));
        $this->populate_column('actions', $this->make_actions($info));
        $this->end_row();
    }

    function add_header($id, $header, $level = 2) {
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
    function end_form($actions = null) {
        if($this->intable) $this->form .= '</ul>';
        $this->form .= '</form>';
    }

    function render() {
        echo $this->form;
    }

    private function start_row($info, $class) {
        $this->form .= '<li id="extensionplugin__'.hsc($info->html_id).'" class="'.$class.'">';
    }
    private function populate_column($class, $html) {
        $this->form .= '<div class="'.$class.' col">'.$html.'</div>';
    }
    private function end_row() {
        $this->form .= '</li>'.DOKU_LF;
    }

    /**
     * Generate documentation/title url for a single plugin
     */
    function make_homepagelink($info) {
        if(!empty($info->dokulink)) {
            $info->url = "http://www.dokuwiki.org/".$info->dokulink;
        }

        if(!empty($info->url)) {
            return $this->make_link($info, 'urlextern');
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
        if($info->is_protected) $class.=  ' protected';
        if($this->showinfo) $class.= ' showinfo';
        return $class;
    }

    function make_author($info) {
        global $ID;

        if(!empty($info->author)) {

            $params = array(
                'do'=>'admin',
                'page'=>'extension',
                'tab'=>'search',
                'q'=>'author:'.$info->author
            );
            $url = wl($ID, $params);
            return '<a href="'.$url.'" title="'.$this->manager->getLang('author_hint').'" >'.hsc($info->author).'</a>';
        }
        return "<em>".$this->manager->getLang('unknown')."</em>";
    }

    function make_screenshot($info) {
        if(!empty($info->screenshoturl)) {
            $img = '<a title="'.hsc($info->displayname).'" href="'.$info->screenshoturl.'" target="_blank">'.
                   '<img alt="'.hsc($info->displayname).'" width="120" height="70" src="'.$info->thumbnailurl.'" />'.
                   '</a>';
        } elseif($info->is_template) {
            $img = '<img alt="template" width="120" height="70" src="'.DOKU_BASE.'lib/plugins/extension/images/template.png" />';

        } else {
            $img = '<img alt="plugin" width="120" height="70" src="'.DOKU_BASE.'lib/plugins/extension/images/plugin.png" />';
        }
        return '<div class="screenshot" >'.$img.'<span></span></div>';
    }

    /**
     * Extension main description
     */
    function make_legend($info) {
        global $lang;

        $return  = '<div>';
        $return .= '<h2>';
        $return .= '<strong>'.hsc($info->displayname).'</strong>';
        $return .= ' by '.$this->make_author($info);
        $return .= '</h2>';

        $return .= $this->make_screenshot($info);

        if ($info->popularity && !$info->is_bundled) {
            $progressCount = $info->popularity;
            $progressWidth = round(100*$progressCount/$this->helper->repo['maxpop']);
            $popularityText = $this->manager->getLang('popularity').' '.$progressCount.'/'.$this->helper->repo['maxpop'];
            $return .= '<div class="popularity" title="'.$popularityText.'"><div style="width: '.$progressWidth.'%;"><span>'.$progressCount.'</span></div></div>';
        }

        $return .= '<p>';
        if(!empty($info->description)) {
            $return .=  hsc($info->description).' ';
        }
        $return .= '</p>';

        $return .= $this->make_linkbar($info);
        $return .= $this->make_action('info', $info, $this->manager->getLang('btn_info'));
        if ($this->showinfo) {
            $return .= $this->make_info($info);
        }
        $return .= $this->make_noticearea($info);
        $return .= '</div>';
        return $return;
    }

    function make_linkbar($info) {
        $return  = '<span class="linkbar">';
        $return .= $this->make_homepagelink($info);
        if ($info->bugtracker) {
            $return .= ' <a href="'.hsc($info->bugtracker).'" title="'.hsc($info->bugtracker).'" class ="interwiki iw_dokubug">'.$this->manager->getLang('bugs_features').'</a>';
        }
        if(!empty($info->tags) && is_array($info->tags['tag'])) {
            foreach ($info->tags['tag'] as $tag) {
                $return .= $this->manager->handler->html_taglink($tag);
            }
        }
        $return .= '</span>';
        return $return;
    }

    /**
     * Notice area
     */
    function make_noticearea($info) {
        $return = '';
        if($info->missing_dependency()) {
            $return .= '<div class="msg error">'.
                            sprintf($this->manager->getLang('missing_dependency'), implode(', ', array_map(array($this->helper, 'make_extensionsearchlink'), $info->missing_dependency))).
                        '</div>';
        }
        if($info->wrong_folder()) {
            $return .= '<div class="msg error">'.
                            sprintf($this->manager->getLang('wrong_folder'), hsc($info->id), hsc($info->base)).
                        '</div>';
        }
        if(!empty($info->securityissue)) {
            $return .= '<div class="msg error">'.
                            sprintf($this->manager->getLang('security_issue'), hsc($info->securityissue)).
                        '</div>';
        }
        if(!empty($info->securitywarning)) {
            $return .= '<div class="msg notify">'.
                            sprintf($this->manager->getLang('security_warning'), hsc($info->securitywarning)).
                        '</div>';
        }
        if($info->update_available) {
            $return .=  '<div class="msg notify">'.
                            sprintf($this->manager->getLang('update_available'), hsc($info->lastupdate)).
                        '</div>';
        }
        if($info->url_changed()) {
            $return .=  '<div class="msg notify">'.
                            sprintf($this->manager->getLang('url_change'), hsc($info->repo['downloadurl']), hsc($info->log['downloadurl'])).
                        '</div>';
        }
        return $return;
    }

    /**
     * Create a link from the given URL
     *
     * Shortens the URL for display
     *
     * @param string $url
     *
     * @return string  HTML link
     */
    function shortlink($url){
        $link = parse_url($url);

        $base = $link['host'];
        if($link['port']) $base .= $base.':'.$link['port'];
        $long = $link['path'];
        if($link['query']) $long .= $link['query'];

        $name = shorten($base, $long, 55);

        return '<a href="'.hsc($url).'" class="urlextern">'.hsc($name).'</a>';
    }

    /**
     * Plugin/template details
     */
    function make_info($info) {
        $default = $this->manager->getLang('unknown');
        $return = '<dl class="details">';

        if (!$info->is_bundled) {
            $return .= '<dt>'.$this->manager->getLang('downloadurl').'</dt>';
            $return .= '<dd>';
            $return .= (!empty($info->downloadurl) ? $this->shortlink($info->downloadurl) : $default);
            $return .= '</dd>';

            $return .= '<dt>'.$this->manager->getLang('repository').'</dt>';
            $return .= '<dd>';
            $return .= (!empty($info->sourcerepo) ? $this->shortlink($info->sourcerepo) : $default);
            $return .= '</dd>';
        }

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

        if(!empty($info->install_date)) {
            $return .= '<dt>'.$this->manager->getLang('installed').'</dt>';
            $return .= '<dd>';
            $return .= hsc($info->install_date);
            $return .= '</dd>';
        }

        $return .= '<dt>'.$this->manager->getLang('provides').'</dt>';
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
// TODO: add donate button
//        if ($info->donationurl) {
//            $return .= '<a href="'.hsc($info->donationurl).'" class="donate" title="'.$this->manager->getLang('donate').'"></a>';
//        }
        $return .= '</dl>';
        return $return;
    }

    function make_linklist($links) {
        $return = '';
        foreach ($links as $link) {
            $dokulink = hsc($link);
            if (strpos($link, 'template:') !== 0) $dokulink = 'plugin:'.$dokulink;
            $return .= '<a href="http://www.dokuwiki.org/'.$dokulink.'" title="'.$dokulink.'" class="interwiki iw_doku">'.$link.'</a> ';
        }
        return $return;
    }

    function make_actions($info) {
        $return = '';
        foreach($this->actions as $act => $text) {
            if($info->{"can_".$act}()) {
                $this->actions_shown[$act] = true;
                $return .= $this->make_action($act, $info, $text);
            }
        }

        if (!$info->is_installed) {
            $return .= ' <span class="version">'.$this->manager->getLang('available_version').' ';
            $return .= ($info->lastupdate ? hsc($info->lastupdate) : $this->manager->getLang('unknown')).'</span>';
        }

        if(false && !empty($this->possible_errors)) { // TODO: display errors in a better way
            $return .= '<p>';
            foreach($this->possible_errors as $error => $text) {
                if($info->$error()) {
                    if(is_array($info->$error)) {
                        $return .= "(<em>".$text." ".hsc(implode(', ', $info->$error))."</em>)";
                    } else {
                        $return .= "(<em>".$text."</em>)";
                    }
                }
            }
            $return .= '</p>';
        }
        return $return;
    }

    function make_action($action, $info, $text) {
        $title = $revertAction = $extraClass = '';

        switch ($action) {
            case 'info':
                $title = 'title="'.$this->manager->getLang('btn_info').'"';
                if ($this->showinfo) {
                    $revertAction = '-';
                    $extraClass   = 'close';
                }
                break;
            case 'download':
            case 'reinstall':
                $title = 'title="'.$info->downloadurl.'"';
                break;
        }

        $classes = 'button '.$action.' '.$extraClass;
        $name    = 'fn['.$action.']['.$revertAction.$info->cmdkey.']';

        return '<input class="'.$classes.'" name="'.$name.'" type="submit" value="'.$text.'" '.$title.' />';
    }

}
