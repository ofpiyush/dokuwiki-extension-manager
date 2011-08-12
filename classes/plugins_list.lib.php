<?php
/**
 * Plugin Manager plugins list
 *
 * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class plugins_list {

    var $rowadded = false;
    protected $form = null;
    protected $actions = array();
    protected $type = "plugin";
    protected $id = null;
    protected $m = null;
    protected $columns = array();
    protected $intable =false;
    

    /**
     * Plugins list constructor
     * Starts the form, table and sets up actions available to the user
     */
    function __construct(plugins_base $manager,$id,$actions = array(),$type ="plugin") {
        $this->m = $manager;
        $this->type = $type;
        $this->id = $id;
        //$this->actions[''] = $this->m->get_lang('please_choose');
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
    function add_row($class,$info,$actions,$checkbox = array()) {
        if($this->intable) {
            $this->rowadded = true;
            $this->start_row($class);
            $this->populate_column('checkbox',$this->make_checkbox($info,$checkbox));
            $this->populate_column('legend',$this->make_legend($info,$class));
            $this->populate_column('inforight',$this->make_inforight($info,$class));
            if(stripos($class,'template') !== false ) {
                $this->populate_column('screenshot',$this->make_screenshot($info));
            }
            $this->populate_column('actions','<p>'.$actions.'</p>');
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
    function end_form() {
        if($this->intable) $this->form .= '</table>';
        if($this->rowadded) {
            $this->form .= '<div class="bottom">';
            //$this->form .= '<select id="'.$this->id.'submit" class="quickselect" size="1" name="action">';
            foreach($this->actions as $value => $text) {
                //$this->form .= '<option value="'.$value.'">'..'</option>';
                $this->form .= '<input class="button" name="fn['.$value.']" type="submit" value="'.hsc($text).'" />';
            }
            //$this->form .= '</select>';
            //$this->form .= '<input class="button" type="submit" value="'.$this->m->get_lang('btn_go').'" />';
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
        return "<em>".$this->m->get_lang('unknown')."</em>";
    }
    private function make_link($info, $class) {
        return '<a href="'.hsc($info->url).'" title="'.hsc($info->url).'" class ="'.$class.'">'.hsc($info->name).'</a>';
    }

    private function make_inforight($info,$class) {
        $return = '<p><label for="'.$this->id.hsc($info->id).'">';
        $return .= '<strong>'.$this->m->get_lang('version').'</strong> ';
        if(empty($info->version)) $info->version = '<em>'.$this->m->get_lang('unknown').'</em>';
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
                            sprintf($this->m->get_lang('update_available'),hsc($info->newversion)).
                        '</div>';
        }
        if(!empty($info->securityissue)) {
            $return .= '<div class="error">'.
                            '<strong>'.$this->m->get_lang('security_issue').'</strong> '.
                            hsc($info->securityissue).
                        '</div>';
        }
        if(!empty($info->securitywarning)) {
            $return .= '<div class="notify">'.
                            '<strong>'.$this->m->get_lang('security_warning').'</strong> '.
                            hsc($info->securitywarning).
                        '</div>';
        }
        if(stripos($class,'infoed') !== false) {
            $return .= $this->make_infoed($info);
        }
        return $return;
    }
    private function make_infoed($info) {
        $return .= '<p>';
        $default = "<em>".$this->m->get_lang('unknown')."</em>";
        $return .= '<strong>'.hsc($this->m->get_lang('author')).'</strong> '.$this->make_author($info).'<br/>';
        $return .= '<strong>'.hsc($this->m->get_lang('source')).'</strong> '.
                (!empty($info->downloadurl) ? hsc($info->downloadurl) : $default).'<br/>';
        $return .= '<strong>'.hsc($this->m->get_lang('components')).':</strong> '.
                (!empty($info->type) ? hsc($info->type) : $default).'<br/>';
        $return .= '<strong>'.hsc($this->m->get_lang('installed')).'</strong> <em>'.
                (!empty($info->installed) ? hsc($info->installed): $default).'</em><br/>';
        $return .= '<strong>'.hsc($this->m->get_lang('lastupdate')).'</strong> <em>'.
                (!empty($info->updated) ? hsc($info->updated) : $default).'</em><br/>';
        $return .= '<strong>'.$this->m->get_lang('tags').'</strong> '.
                (!empty($info->tags) ? hsc(implode(', ',(array)$info->tags['tag'])) : $default).'<br/>';
        $return .= '</p>';
        return $return;
    }
    private function make_checkbox($info,$checkbox) {
        $checked =" ";
        if(!empty($checkbox)) {
            foreach($checkbox as $key=>$value)
                $checked .= $key.'="'.$value.'" ';
        }
        return  '<label for="'.$this->id.hsc($info->id).'" >'.
                    '<input id="'.$this->id.hsc($info->id).'" type="checkbox"'.
                    ' name="checked[]" value="'.$info->id.'" '.$checked.' />'.
                '</label>';
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
