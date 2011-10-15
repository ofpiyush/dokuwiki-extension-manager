<?php
/**
 * Plugin Manager plugins list
 *
 * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_plugins_list_lib {

    var $rowadded = false;
    protected $form = null;
    protected $actions = array();
    protected $possible_errors = array();
    protected $type = "plugin";
    protected $id = null;
    protected $manager = null;
    protected $columns = array();
    protected $intable = false;
    protected $acted = array();
    

    /**
     * Plugins list constructor
     * Starts the form, table and sets up actions available to the user
     */
    function __construct(admin_plugin_extension $manager,$id,$actions = array(),$possible_errors=array(),$type ="plugin") {
        $this->manager = $manager;
        $this->type = $type;
        $this->possible_errors = $possible_errors;
        $this->id = $id;
        
        //$this->actions['info'] = $this->manager->getLang('btn_info');
        $this->actions = array_merge($this->actions,$actions);
        $this->form = '<div class="common">';
    }
    function start_form($starttable = true) {
        $this->form .= '<form id="'.$this->id.'" accept-charset="utf-8" method="post" action="">';
        $hidden['page'] = 'extension';
        //$hidden['fn']   ='multiselect';
        $hidden['do'] = 'admin';
        $hidden['sectok'] = getSecurityToken();
        if($type == "template")
            $hidden['template'] = 'template';
        $this->add_hidden($hidden);
        if($starttable) {
            $this->intable = true;
            $this->form .= '<table class="inline">';
        }
    }
    /**
     * Build single row of plugin table
     * @param string $class    class of the table row
     * @param array  $info     a single plugin from repo cache
     * @param string $actions  html for what goes into the action column
     * @param array  $checkbox the optional parameters to be passed in for the checkbox (use-case disabling downloads)
     */
    function add_row($info) {
        if($this->intable) {
            $this->rowadded = true;
            $this->start_row($info,$this->make_class($info));
            $this->populate_column('checkbox',$this->make_checkbox($info,$checkbox));
            $this->populate_column('legend',$this->make_legend($info,$class));
            $this->populate_column('version',$this->make_version($info,$class));
            if($this->type == 'template') {
                $this->populate_column('screenshot',$this->make_screenshot($info));
            }
            $this->populate_column('actions','<p>'.$this->make_actions($info).'</p>');
            $this->end_row();
        }
    }

    function add_header($header,$level=2) {
        $this->form .='<h'.$level.'>'.hsc($header).'</h'.$level.'>';
    }
    function add_p($data) {
        $this->form .= '<p>'.$data.'</p>';
    }
    function add_hidden(array $array) {
        foreach ($array as $key => $value) {
            $this->form .= '<div class="no"><input type="hidden" name="'.$key.'" value="'.$value.'" /></div>';
        }        
    }

    /**
     * Add closing tags
     */
    function end_form($actions = array()) {
        if($this->intable) $this->form .= '</table>';
        if($this->rowadded) {
            $acted = array_filter($this->acted);
            if(!empty($acted)) {
            $this->form .= '<div class="checks"><span class="checkall">['.$this->manager->getLang('select_all').']</span>'.
                            '  <span class="checknone">['.$this->manager->getLang('select_none').']</span></div>';
            }
            $this->form .= '<div class="bottom">';
            foreach($this->actions as $value => $text) {
                if(!in_array($value,$actions) || empty($acted[$value])) continue;
                $this->form .= '<input class="button" name="fn['.$value.']" type="submit" value="'.hsc($text).'" />';
            }
            $this->form .= '</div>';
        }
        $this->form .= '</form>';
        $this->form .= '</div>';
    }
    function render() {
        echo $this->form;
    }
    private function start_row($info,$class) {
        $this->form .= '<tr id="extensionplugin__'.$this->manager->tab.$info->id.'" class="'.$class.'">'; 
    }
    private function populate_column($class,$html) {
        $this->form .= '<td class="'.$class.'">'.$html.'</td>';
    }
    private function end_row() {
        $this->form .= '</tr>';
    }


    /**
     * Generate title url for a single plugin
     * @param array $info a single plugin from repo cache
     * @return string url or title of the plugin
     */
    function make_title($info) {
        $name = hsc($info->name);
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

        return  hsc($info->name);
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
        if($info->highlight()) $class.= ' highlight';
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
    function make_link($info, $class) {
        return '<a href="'.hsc($info->url).'" title="'.hsc($info->url).'" class ="'.$class.'">'.hsc($info->name).'</a>';
    }

    function make_version($info,$class) {
        $return = '<p>';
        $return .= '<strong>'.$this->manager->getLang('version').'</strong> ';
        if(empty($info->version)) $info->version = '<em>'.$this->manager->getLang('unknown').'</em>';
        $return .= $info->version."</p>";
        return $return;
    }

    function make_screenshot($info) {
        $return = '';
        if(!empty($info->screenshoturl)) {
            if($info->screenshoturl[0] == ':')
                $info->screenshoturl = 'http://www.dokuwiki.org/_media/'.$info->screenshoturl;
            $return .= '<a title="'.hsc($info->name).'" href="'.$info->screenshoturl.'">'.
                    '<img alt="'.hsc($info->name).'" width="80" src="'.hsc($info->screenshoturl).'" />'.
                    '</a>';
        }
        return $return;
    }
    function make_legend($info,$class) {
        $return = '<p class="head">'.
                    '<label for="'.$this->id.'_'.hsc($info->id).'">'.hsc($info->id).':</label>'.
                    $this->make_title($info).
                  '</p>';
        if(!empty($info->description)) {
            $return .=  '<p>'.
                        hsc($info->description).'</p>';
        }
        if(!empty($info->newversion)) {
            $return .=  '<div class="notify">'.
                            sprintf($this->manager->getLang('update_available'),hsc($info->newversion)).
                        '</div>';
        }
        if($info->wrong_folder()) {
            global $lang;
            $return .= '<div class="error">'.
                            sprintf($lang['plugin_insterr'],hsc($info->id),hsc($info->base)).
                        '</div>';
        }
        if(!empty($info->securityissue)) {
            $return .= '<div class="error">'.
                            '<strong>'.$this->manager->getLang('security_issue').'</strong> '.
                            hsc($info->securityissue).
                        '</div>';
        }
        if(!empty($info->securitywarning)) {
            $return .= '<div class="notify">'.
                            '<strong>'.$this->manager->getLang('security_warning').'</strong> '.
                            hsc($info->securitywarning).
                        '</div>';
        }
        if($this->manager->showinfo == $info->id) {
            $return .= $this->make_info($info);
        }
        return $return;
    }
    function make_info($info) {
        $return .= '<p>';
        $default = $this->manager->getLang('unknown');
        $return .= '<strong>'.hsc($this->manager->getLang('author')).'</strong> '.$this->make_author($info).'<br/>';
        $return .= '<strong>'.hsc($this->manager->getLang('source')).'</strong> '.
                (!empty($info->downloadurl) ? hsc($info->downloadurl) : $default).'<br/>';
        $return .= '<strong>'.hsc($this->manager->getLang('components')).':</strong> '.
                (!empty($info->type) ? hsc($info->type) : $default).'<br/>';
        if($this->manager->tab != "search") {
            $return .= '<strong>'.hsc($this->manager->getLang('installed')).'</strong> <em>'.
                    (!empty($info->installed) ? hsc($info->installed): $default).'</em><br/>';
            $return .= '<strong>'.hsc($this->manager->getLang('lastupdate')).'</strong> <em>'.
                    (!empty($info->updated) ? hsc($info->updated) : $default).'</em><br/>';
        }
        if(!empty($info->relations['depends']['id'])) {
            $return .= '<strong>'.$this->manager->getLang('depends').':</strong> '.
                hsc(implode(', ',(array)$info->relations['depends']['id'])).'<br/>';
        }
        if(!empty($info->relations['similar']['id'])) {
            $return .= '<strong>'.$this->manager->getLang('similar').':</strong> '.
                hsc(implode(', ',(array)$info->relations['similar']['id'])).'<br/>';
        }
        if(!empty($info->relations['conflicts']['id'])) {
            $return .= '<strong>'.$this->manager->getLang('conflicts').':</strong> '.
                hsc(implode(', ',(array)$info->relations['conflicts']['id'])).'<br/>';
        }
        $return .= '<strong>'.$this->manager->getLang('tags').'</strong> '.
                (!empty($info->tags) ? hsc(implode(', ',(array)$info->tags['tag'])) : $default).'<br/>';
        $return .= '</p>';
        return $return;
    }
    function make_checkbox($info) {
        $checked =" ";
        if(!$info->can_select()) {
            $checked .= 'disabled="disabled"';
        }
        return '<input id="'.$this->id.'_'.hsc($info->id).'" type="checkbox"'.
               ' name="checked[]" value="'.$info->id.'" '.$checked.' />';
    }
    function make_actions($info) {
        $extra =  null;
        if($this->manager->tab == "search" ) {
            if(!empty($this->manager->handler->term))
                $extra['term'] = $this->manager->handler->term;
            if(!empty($this->manager->handler->extra)) {
                $extra = array_merge($extra,$this->manager->handler->extra);
            }
        }
        $return = $this->make_action('info',$info->id,$this->manager->getLang('btn_info'),$extra);
        foreach($this->actions as $act => $text) {
            if($info->{"can_".$act}()) {
                $this->acted[$act] = true;
                $return .= " | ".$this->make_action($act,$info->id,$text);
            }
        }
        if(!empty($this->possible_errors)) {
            foreach($this->possible_errors as $error => $text) {
                if($info->$error()) {
                    if(!empty($info->$error)) {
                        $return .= "<br />(<em>".$text." ".hsc(implode(', ',$info->$error))."</em>)";
                    } else {
                        $return .= "<br />(<em>".$text."</em>)";
                    }
                }
            }
        }
        return $return;
    }
    function make_action($action,$id,$text,$extra =null) {
        global $ID;
        $params = array(
            'do'=>'admin',
            'page'=>'extension',
            'tab' => $this->manager->tab,
            'fn'=>$action,
            'checked[]'=>$id,
            'sectok'=>getSecurityToken()
        );
        if(!empty($extra)) $params = array_merge($params,$extra);
        $url = wl($ID,$params);
        return '<a href="'.$url.'" class="'.$action.'" title="'.$id.' : '.$text.'">'.$text.'</a>';
    }
}
