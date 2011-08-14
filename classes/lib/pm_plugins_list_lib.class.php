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
    function __construct(pm_base_tab $tab,$id,$actions = array(),$possible_errors=array(),$type ="plugin") {
        $this->tab = $tab;
        $this->type = $type;
        $this->possible_errors = $possible_errors;
        $this->id = $id;
        
        //$this->actions['info'] = $this->tab->manager->getLang('btn_info');
        $this->actions = array_merge($this->actions,$actions);
        $this->form = '<div class="common">';
    }
    function start_form($starttable = true) {
        $this->form .= '<form id="'.$this->id.'" accept-charset="utf-8" method="post" action="">';
        $hidden['page'] = 'plugin';
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
    function add_row($class,$info) {
        if($this->intable) {
            $this->rowadded = true;
            $this->start_row($class);
            $this->populate_column('checkbox',$this->make_checkbox($info,$checkbox));
            $this->populate_column('legend',$this->make_legend($info,$class));
            $this->populate_column('inforight',$this->make_inforight($info,$class));
            if(stripos($class,'template') !== false ) {
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
            $this->form .= '<div class="checks"><span class="checkall">['.$this->tab->manager->getLang('select_all').']</span>'.
                            '  <span class="checknone">['.$this->tab->manager->getLang('select_none').']</span></div>';
            }
            $this->form .= '<div class="bottom">';

            //$this->form .= '<select id="'.$this->id.'submit" class="quickselect" size="1" name="action">';
            foreach($this->actions as $value => $text) {
                if(!in_array($value,$actions) || empty($acted[$value])) continue;
                //$this->form .= '<option value="'.$value.'">'..'</option>';
                $this->form .= '<input class="button" name="fn['.$value.']" type="submit" value="'.hsc($text).'" />';
            }
            //$this->form .= '</select>';
            //$this->form .= '<input class="button" type="submit" value="'.$this->tab->manager->getLang('btn_go').'" />';
            $this->form .= '</div>';
        }
        $this->form .= '</form>';
        $this->form .= '</div>';
    }
    function render() {
        echo $this->form;
    }
    private function start_row($class) {
        $this->form .= '<tr class="'.$class.'">'; 
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
    private function make_title($info) {
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

    private function make_author($info) {
        if(!empty($info->author)) {
            if(!empty($info->email)) {
                return '<a href="mailto:'.hsc($info->email).'">'.hsc($info->author).'</a>';
            }
            return hsc($info->author);
        }
        return "<em>".$this->tab->manager->getLang('unknown')."</em>";
    }
    private function make_link($info, $class) {
        return '<a href="'.hsc($info->url).'" title="'.hsc($info->url).'" class ="'.$class.'">'.hsc($info->name).'</a>';
    }

    private function make_inforight($info,$class) {
        $return = '<p><label for="'.$this->id.hsc($info->id).'">';
        $return .= '<strong>'.$this->tab->manager->getLang('version').'</strong> ';
        if(empty($info->version)) $info->version = '<em>'.$this->tab->manager->getLang('unknown').'</em>';
        $return .= $info->version."</label></p>";
        return $return;
    }

    private function make_screenshot($info) {
        $return = '<label for="'.$this->id.hsc($info->id).'">';
        if(!empty($info->screenshoturl)) {
            if($info->screenshoturl[0] == ':')
                $info->screenshoturl = 'http://www.dokuwiki.org/_media/'.$info->screenshoturl;
            $return .= '<a title="'.hsc($info->name).'" href="'.$info->screenshoturl.'">'.
                    '<img alt="'.hsc($info->name).'" width="80" src="'.hsc($info->screenshoturl).'" />'.
                    '</a>';
        }
        $return .= '</label>';
        return $return;
    }
    private function make_legend($info,$class) {
        $return = '<p class="head"> <a name="pminfoed__'.$info->id.'" ></a>'.
                    '<label for="'.$this->id.hsc($info->id).'">'.
                    $this->make_title($info).
                    '</label>'.
                  '</p>';
        if(!empty($info->description)) {
            $return .=  '<p><label for="'.$this->id.hsc($info->id).'">'.
                        hsc($info->description).'</label></p>';
        }
        if(!empty($info->newversion)) {
            $return .=  '<div class="notify">'.
                            sprintf($this->tab->manager->getLang('update_available'),hsc($info->newversion)).
                        '</div>';
        }
        if(!empty($info->securityissue)) {
            $return .= '<div class="error">'.
                            '<strong>'.$this->tab->manager->getLang('security_issue').'</strong> '.
                            hsc($info->securityissue).
                        '</div>';
        }
        if(!empty($info->securitywarning)) {
            $return .= '<div class="notify">'.
                            '<strong>'.$this->tab->manager->getLang('security_warning').'</strong> '.
                            hsc($info->securitywarning).
                        '</div>';
        }
        if($this->tab->manager->showinfo == $info->id) {
            $return .= $this->make_infoed($info);
        }
        return $return;
    }
    private function make_infoed($info) {
        $return .= '<p>';
        $default = "<em>".$this->tab->manager->getLang('unknown')."</em>";
        $return .= '<strong>'.hsc($this->tab->manager->getLang('author')).'</strong> '.$this->make_author($info).'<br/>';
        $return .= '<strong>'.hsc($this->tab->manager->getLang('source')).'</strong> '.
                (!empty($info->downloadurl) ? hsc($info->downloadurl) : $default).'<br/>';
        $return .= '<strong>'.hsc($this->tab->manager->getLang('components')).':</strong> '.
                (!empty($info->type) ? hsc($info->type) : $default).'<br/>';
        if($this->tab->manager->tab != "search") {
            $return .= '<strong>'.hsc($this->tab->manager->getLang('installed')).'</strong> <em>'.
                    (!empty($info->installed) ? hsc($info->installed): $default).'</em><br/>';
            $return .= '<strong>'.hsc($this->tab->manager->getLang('lastupdate')).'</strong> <em>'.
                    (!empty($info->updated) ? hsc($info->updated) : $default).'</em><br/>';
        }
        if(!empty($info->relations['depends']['id'])) {
            $return .= '<strong>'.$this->tab->manager->getLang('depends').':</strong> '.
                hsc(implode(', ',(array)$info->relations['depends']['id'])).'<br/>';
        }
        if(!empty($info->relations['similar']['id'])) {
            $return .= '<strong>'.$this->tab->manager->getLang('similar').':</strong> '.
                hsc(implode(', ',(array)$info->relations['similar']['id'])).'<br/>';
        }
        if(!empty($info->relations['conflicts']['id'])) {
            $return .= '<strong>'.$this->tab->manager->getLang('conflicts').':</strong> '.
                hsc(implode(', ',(array)$info->relations['conflicts']['id'])).'<br/>';
        }
        $return .= '<strong>'.$this->tab->manager->getLang('tags').'</strong> '.
                (!empty($info->tags) ? hsc(implode(', ',(array)$info->tags['tag'])) : $default).'<br/>';
        $return .= '</p>';
        return $return;
    }
    private function make_checkbox($info) {
        $checked =" ";
        if(!$info->can_select()) {
            $checked .= 'disabled="disabled"';
        }
        return  '<label for="'.$this->id.hsc($info->id).'" >'.
                    '<input id="'.$this->id.hsc($info->id).'" type="checkbox"'.
                    ' name="checked[]" value="'.$info->id.'" '.$checked.' />'.
                '</label>';
    }
    private function make_actions($info) {
        $extra =  null;
        if($this->tab->manager->tab == "search" ) {
            if(!empty($this->t->term))
                $extra['term'] = $this->t->term;
            if(!empty($this->t->extra)) {
                $extra = array_merge($extra,$this->t->extra);
            }
        }
        $return = $this->make_action('info',$info->id,$this->tab->manager->getLang('btn_info'),$extra);
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
            'page'=>'plugin',
            'tab' => $this->tab->manager->tab,
            'fn'=>$action,
            'checked[]'=>$id,
            'sectok'=>getSecurityToken()
        );
        if(!empty($extra)) $params = array_merge($params,$extra);
        $url = wl($ID,$params);
        return '<a href="'.$url.'" class="'.$action.'" title="'.$url.'">'.$text.'</a>';
    }
    // not being used now
    function enabled_tpl_row($enabled,$actions) {
        $class ="enabled template";
        if(!empty($this->enabled['securityissue'])) $class .= " secissue";
        $this->form .= '<tr class="'.$class.'"><td colspan="5" >';
        if(!empty($enabled['screenshoturl'])) {
            if($enabled['screenshoturl'][0] == ':') $enabled['screenshoturl'] = 'http://www.dokuwiki.org/_media/'.$enabled['screenshoturl'];
            $this->form .= '<img alt="'.$enabled['name'].'" src="'.hsc($enabled['screenshoturl']).'" />';
        }
        $this->form .= '<div class="legend"><span class="head">';
        $this->form .= $this->make_title($enabled);
        $this->form .= '</span></div>';
        if(!empty($enabled['description'])) {
            $this->form .= '<p>'.hsc($enabled['description']).'</p>';
        }
        $this->form .= '</td></tr>';
    }
    
}
