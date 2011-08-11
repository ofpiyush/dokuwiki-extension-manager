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

    /**
     * Plugins list constructor
     * Starts the form, table and sets up actions available to the user
     */
    function __construct(ap_manage $manager,$id,$actions,$type ="plugin") {
        $this->manager = $manager;
        $this->type = $type;
        $this->actions[''] = '-Please Choose-';
        $this->actions = array_merge($this->actions,$actions);
        $this->form = new Doku_Form($id);
        $this->form->addHidden('page','plugin');
        $this->form->addHidden('fn','multiselect');
        $this->form->addElement('<table class="inline">');
        if($type == "template")
            $this->form->addHidden('template','template');
    }

    function enabled_tpl_row($enabled,$actions) {
        $class ="enabled template";
        if(!empty($this->enabled['securityissue'])) $class .= " secissue";
        $this->form->addElement('<tr class="'.$class.'"><td colspan="4" >');
        if(!empty($enabled['screenshoturl'])) {
            if($enabled['screenshoturl'][0] == ':') $enabled['screenshoturl'] = 'http://www.dokuwiki.org/_media/'.$enabled['screenshoturl'];
            $this->form->addElement('<img alt="'.$enabled['name'].'" src="'.hsc($enabled['screenshoturl']).'" />');
        }
        $this->form->addElement('<div class="legend"><span class="head">');
        $this->form->addElement($this->make_title($enabled));
        $this->form->addElement('</span></div>');
        if(!empty($enabled['description'])) {
            $this->form->addElement('<p>'.hsc($enabled['description']).'</p>');
        }
        $this->form->addElement('</td></tr>');
    }
    /**
     * Build single row of plugin table
     * @param string $class    class of the table row
     * @param array  $info     a single plugin from repo cache
     * @param string $actions  html for what goes into the action column
     * @param array  $checkbox the optional parameters to be passed in for the checkbox (use-case disabling downloads)
     */
    function add_row($class,$info,$actions,$checkbox = array()) {
        $this->rowadded = true;
        $this->form->addElement('<tr class="'.$class.'">');
        $checked ="";
        if(!empty($checkbox)) {
            foreach($checkbox as $key=>$value)
                $checked .= $key.'="'.$value.'"';
        }
        $this->form->addElement('<td class="checkbox"><input type="checkbox" name="checked[]" value="'.$info['id'].'" '.$checked.' /></td>');
        $this->form->addElement('<td class="legend">');
        $this->form->addElement('<span class="head">'.$this->make_title($info).'</span>');
        if(stripos($class,'infoed') !== false) {
            $this->form->addElement('<span class="inforight"><p>');
            if(!empty($info['author'])) {
                if(!empty($info['email']))
                    $this->form->addElement('<strong>'.hsc($this->manager->lang['author']).'</strong> <a href="mailto:'.hsc($info['email']).'">'.hsc($info['author']).'</a><br/>');
                else
                    $this->form->addElement('<strong>'.hsc($this->manager->lang['author']).'</strong> '.hsc($info['author']).'<br/>');
            }
            if(!empty($info['tags']))
                $this->form->addElement('<strong>'.hsc($this->manager->lang['tags']).'</strong> '.hsc(implode(', ',(array)$info['tags']['tag'])).'<br/>');
            $this->form->addElement('</p></span>');
        }
        if(!empty($info['description'])) {
            $this->form->addElement("<p>".hsc($info['description'])."</p>");
        }
        if(!empty($info['securityissue'])) {
            $this->form->addElement('<div class="error">'.'<strong>Security Issue:</strong> '.hsc($info['securityissue']).'</div>');
        }
        if(!empty($info['securitywarning'])) {
            $this->form->addElement('<div class="notify">'.'<strong>Security Warning:</strong> '.hsc($info['securitywarning']).'</div>');
        }
        if(stripos($class,'infoed') !== false) {
            $this->form->addElement('<p>');
            if(!empty($info['type'])) {
                $this->form->addElement('<strong>'.hsc($this->manager->lang['components']).':</strong> '.hsc($info['type']).'<br/>');
            }
            if(!empty($info['installed']))
                $this->form->addElement('<strong>'.hsc($this->manager->lang['installed']).'</strong> <em>'.hsc($info['installed']).'</em><br/>');
            if(!empty($info['updated']))
                $this->form->addElement('<strong>'.hsc($this->manager->lang['lastupdate']).'</strong> <em>'.hsc($info['updated']).'</em><br/>');
            $this->form->addElement('</p>');
        }
        if(stripos($class,'template') !== false ) {
            $this->form->addElement('</td><td class="screenshot">');
            if(!empty($info['screenshoturl'])) {
                if($info['screenshoturl'][0] == ':') $info['screenshoturl'] = 'http://www.dokuwiki.org/_media/'.$info['screenshoturl'];
                $this->form->addElement('<a title="'.hsc($info['name']).'" href="'.$info['screenshoturl'].'"><img alt="'.hsc($info['name']).'" width="80" src="'.hsc($info['screenshoturl']).'" /></a>');
            }
        }
        $this->form->addElement('</td>');
        $this->form->addElement('<td class="actions"><p>'.$actions.'</p></td></tr>');
    }

    /**
     * Add closing tags and render the form
     * @param string $name Name of the event to trigger
     */
    function render($name = null) {
        $this->form->addElement('</table>');
        if($this->rowadded) {
            $this->form->addElement('<div class="bottom">');
            $this->form->addElement(form_makeMenuField('action',$this->actions,'','Action: ','','',array('class'=>'quickselect')));//TODO add language
            $this->form->addElement("</div>");
            $this->form->addElement(form_makeButton('submit', 'admin', 'Go' ));
        }
        if($name !== null)
            html_form($name,$this->form);
        else
            $this->form->printForm();
    }

    function get_form() {
        return $this->form;
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

    function make_link($info, $class) {
        return '<a href="'.hsc($info['url']).'" title="'.hsc($info['url']).'" class ="'.$class.'">'.hsc($info['name']).'</a>';
    }
}
