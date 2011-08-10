<?php
/**
 * Plugin Manager plugins list
 *
 * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class plugins_list {

    protected $form = null;
    protected $actions = array();
    protected $type = "plugin";
    protected $rowadded = false;
    protected $id = null;

    /**
     * Plugins list constructor
     * Starts the form, table and sets up actions available to the user
     */
    function __construct(ap_manage $manager,$id,$actions = array(),$type ="plugin") {
        $this->manager = $manager;
        $this->lang = $manager->lang;
        $this->type = $type;
        $this->id = $id;
        $this->actions[''] = $this->lang['please_choose'];
        $this->actions = array_merge($this->actions,$actions);
        $this->form = '';
        $this->form .='<form id="'.$id.'" accept-charset="utf-8" method="post" action="">';
        $hidden['page'] = 'plugin';
        $hidden['fn'] = 'multiselect';
        $hidden['do'] = 'admin';
        if($type == "template")
            $hidden['template'] = 'template';
        $this->add_hidden($hidden);
        $this->form .='<table class="inline">';
    }
    /**
     * Build single row of plugin table
     * @param string $class    class of the table row
     * @param array  $info     a single plugin from repo cache
     * @param string $actions  html for what goes into the action column
     * @param array  $checkbox the optional parameters to be passed in for the checkbox (use-case disabling downloads)
     */
    function add_row($class,$info,$actions,$checkbox = array()) {
        //TODO remove this check when moving to the other template view
        if(!($this->type =='template' && stripos($class,'enabled')))
            $this->rowadded = true;
        $this->form .='<tr class="'.$class.'">';
        $checked ="";
        if(!empty($checkbox)) {
            foreach($checkbox as $key=>$value)
                $checked .= $key.'="'.$value.'"';
        }
        $this->form .='<td class="checkbox"><label for="'.$this->id.hsc($info['id']).'" ><input id="'.$this->id.hsc($info['id']).'" type="checkbox" name="checked[]" value="'.$info['id'].'" '.$checked.' /></label></td>';
        $this->form .='<td class="legend">';
        $this->form .='<p class="head"><label for="'.$this->id.hsc($info['id']).'">'.$this->make_title($info).'</label></p>';
        if(!empty($info['description'])) {
            $this->form .='<p><label for="'.$this->id.hsc($info['id']).'">'.hsc($info['description']).'</label></p>';
        }
        if(!empty($info['newversion'])) {
            $this->form .='<div class="notify">'.sprintf($this->lang['update_available'],hsc($info['newversion'])).'</div>';
        }
        if(!empty($info['securityissue'])) {
            $this->form .='<div class="error">'.'<strong>'.$this->lang['security_issue'].'</strong> '.hsc($info['securityissue']).'</div>';
        }
        if(!empty($info['securitywarning'])) {
            $this->form .='<div class="notify">'.'<strong>'.$this->lang['security_warning'].'</strong> '.hsc($info['securitywarning']).'</div>';
        }
        if(stripos($class,'infoed') !== false) {
            $this->add_infoed($info);
        }
        $this->form .='</td><td class="inforight"><p><label for="'.$this->id.hsc($info['id']).'">';
        $this->add_inforight($class,$info);
        $this->form .='</label></p>';
        if(stripos($class,'template') !== false ) {
            $this->add_screenshot($info);
        }
        $this->form .='</td>';
        $this->form .='<td class="actions"><p>'.$actions.'</p></td></tr>';
    }

    function add_screenshot($info) {
        $this->form .='</td><td class="screenshot"><label for="'.$this->id.hsc($info['id']).'">';
        if(!empty($info['screenshoturl'])) {
            if($info['screenshoturl'][0] == ':') {
                $info['screenshoturl'] = 'http://www.dokuwiki.org/_media/'.$info['screenshoturl'];
            }
            $this->form .='<a title="'.hsc($info['name']).'" href="'.$info['screenshoturl'].'"><img alt="'.hsc($info['name']).'" width="80" src="'.hsc($info['screenshoturl']).'" /></a></label>';
        }
    }

    function add_infoed($info) {
        $this->form .='<p>';
        $default = "<em>".$this->lang['unknown']."</em>";
        $this->form .='<strong>'.hsc($this->lang['author']).'</strong> '.$this->make_author($info).'<br/>';
         $this->form .='<strong>'.hsc($this->lang['source']).'</strong> '.
                (!empty($info['downloadurl']) ? hsc($info['downloadurl']) : $default).'<br/>';
        $this->form .='<strong>'.hsc($this->lang['components']).':</strong> '.
                (!empty($info['type']) ? hsc($info['type']) : $default).'<br/>';
        $this->form .='<strong>'.hsc($this->lang['installed']).'</strong> <em>'.
                (!empty($info['installed']) ? hsc($info['installed']): $default).'</em><br/>';
        $this->form .='<strong>'.hsc($this->lang['lastupdate']).'</strong> <em>'.
                (!empty($info['updated']) ? hsc($info['updated']) : $default).'</em><br/>';
        $this->form .='<strong>'.$this->lang['tags'].'</strong> '.
                (!empty($info['tags']) ? hsc(implode(', ',(array)$info['tags']['tag'])) : $default).'<br/>';
        $this->form .='</p>';
    }
    /**
     * Add closing tags and render the form
     * @param string $name Name of the event to trigger
     */
    function render() {
        $this->form .='</table>';
        if($this->rowadded) {
            $this->form .='<div class="bottom">';
            $this->form .='<label for="'.$this->id.'submit"><span>'.$this->lang['action'].':</span> ';
            $this->form .='<select id="'.$this->id.'submit" class="quickselect" size="1" name="action">';
            foreach($this->actions as $value => $text) {
                $this->form .='<option value="'.$value.'">'.hsc($text).'</option>';
            }
            $this->form .='</select>';
            $this->form .='</label>';
            $this->form .=' <input class="button" type="submit" value="'.$this->lang['btn_go'].'" />';
            $this->form .='</div>';
        }
        $this->form .='</form>';
        echo $this->form;
    }

    function get_form() {
        return $this->form;
    }

    function add_inforight($class,$info) {
        if(in_array($this->id,array('plugins__list','templates__list'))) {
            $this->form .='<strong>'.$this->lang['version'].'</strong> '.$info['version'];
        } elseif(in_array($this->id,array('browse__list','search__result'))) {
            $this->form .='<strong>'.$this->lang['version'].'</strong> ';
            if(!empty($info['lastupdate']))
                $this->form .= $info['lastupdate'];
            else
                $this->form .= '<em>'.$this->lang['unknown'].'</em>';
        }
    }
    /**
     * Generate title url for a single plugin
     * @param array $info a single plugin from repo cache
     * @return string url or title of the plugin
     */
    function make_title($info) {
        $name = hsc($info['name']);
        if(!empty($info['dokulink'])) {
            $info['url'] = "http://www.dokuwiki.org/".$info['dokulink'];
            return $this->make_link($info,"interwiki iw_doku");
        }

        if(!empty($info['url'])) {
            if(preg_match('|^http(s)?://(www.)?dokuwiki.org/(.*)?$|i', $info['url']))
                return $this->make_link($info,"interwiki iw_doku");
            else
                return $this->make_link($info,"urlextern");
        }

        return  hsc($info['name']);
    }

    function add_hidden(array $array) {
        foreach ($array as $key => $value) {
            $this->form .='<div class="no"><input type="hidden" name="'.$key.'" value="'.$value.'" /></div>';
        }        
    }
    
    function make_author($info) {
        if(!empty($info['author'])) {
            if(!empty($info['email'])) {
                return '<a href="mailto:'.hsc($info['email']).'">'.hsc($info['author']).'</a>';
            }
            return hsc($info['author']);
        }
        return "<em>".$this->lang['unknown']."</em>";
    }
    function make_link($info, $class) {
        return '<a href="'.hsc($info['url']).'" title="'.hsc($info['url']).'" class ="'.$class.'">'.hsc($info['name']).'</a>';
    }

    function enabled_tpl_row($enabled,$actions) {
        $class ="enabled template";
        if(!empty($this->enabled['securityissue'])) $class .= " secissue";
        $this->form .='<tr class="'.$class.'"><td colspan="5" >';
        if(!empty($enabled['screenshoturl'])) {
            if($enabled['screenshoturl'][0] == ':') $enabled['screenshoturl'] = 'http://www.dokuwiki.org/_media/'.$enabled['screenshoturl'];
            $this->form .='<img alt="'.$enabled['name'].'" src="'.hsc($enabled['screenshoturl']).'" />';
        }
        $this->form .='<div class="legend"><span class="head">';
        $this->form .=$this->make_title($enabled);
        $this->form .='</span></div>';
        if(!empty($enabled['description'])) {
            $this->form .='<p>'.hsc($enabled['description']).'</p>';
        }
        $this->form .='</td></tr>';
    }
}
