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

    /**
     * Plugins list constructor
     * Starts the form, table and sets up actions available to the user
     */
    function __construct($id,$actions) {
        $this->actions[''] = '-Please Choose-';
        $this->actions = array_merge($this->actions,$actions);
        $this->form = new Doku_Form($id);
        $this->form->addHidden('page','plugin');
        $this->form->addHidden('fn','multiselect');
        $this->form->addElement(form_makeOpenTag('table',array('class'=>'inline')));
    }

    /**
     * Build single row of plugin table
     * @param string $class    class of the table row
     * @param array  $info     a single plugin from repo cache
     * @param string $actions  html for what goes into the action column
     * @param array  $checkbox the optional parameters to be passed in for the checkbox (use-case disabling downloads)
     */
    function add_row($class,$info,$actions,$checkbox = array()) {
        $this->form->addElement(form_makeOpenTag('tr',array('class'=>$class)));
        $this->form->addElement(form_makeOpenTag('td',array('class'=>'checkbox')));
        $this->form->addElement(form_makeCheckboxField('checked[]',$info['id'],'','','',$checkbox));
        $this->form->addElement(form_makeCloseTag('td'));
        $this->form->addElement(form_makeOpenTag('td',array('class'=>'legend')));
        $this->form->addElement(form_makeOpenTag('span',array('class'=>'head')));
        $this->form->addElement($this->make_title($info));
        $this->form->addElement(form_makeCloseTag('span'));
        if(isset($info['description'])) {
            $this->form->addElement(form_makeOpenTag('p'));
            $this->form->addElement(hsc($info['description']));
            $this->form->addElement(form_makeCloseTag('p'));
        }
        $this->form->addElement(form_makeCloseTag('td'));
        $this->form->addElement(form_makeOpenTag('td',array('class'=>'actions')));
        $this->form->addElement(form_makeOpenTag('p'));
        $this->form->addElement($actions);
        $this->form->addElement(form_makeCloseTag('p'));
        $this->form->addElement(form_makeCloseTag('td'));
        $this->form->addElement(form_makeCloseTag('tr'));
    }

    /**
     * Add closing tags and render the form
     * @param string $name Name of the event to trigger
     */
    function render($name = null) {
        $this->form->addElement(form_makeCloseTag('table'));
        $this->form->addElement(form_makeOpenTag('div',array('class'=>'bottom')));
        $this->form->addElement(form_makeMenuField('action',$this->actions,'','Action: ','','',array('class'=>'quickselect')));//TODO add language
        $this->form->addElement(form_makeCloseTag('div'));
        $this->form->addElement(form_makeButton('submit', 'admin', 'Go' ));
        if($name !== null)
            html_form($name,$this->form);
        else
            $this->form->printForm();
    }

    /**
     * Generate title url for a single plugin
     * @param array $info a single plugin from repo cache
     * @return string url or title of the plugin
     */
    function make_title($info) {
        $name = hsc($info['name']);
        if(array_key_exists('dokulink',$info) && strlen($info['dokulink'])) {
            $info['url'] = "http://dokuwiki.org/".$info['dokulink'];
            return $this->make_link($info,"interwiki iw_doku");
        }

        if(array_key_exists('url',$info) && strlen($info['url'])) {
            // not using preg_match as this is pretty much the only case
            if(stripos('http://dokuwiki.org/',$info['url']) === 0)
                return $this->make_link($info,"interwiki iw_doku");
            else
                return $this->make_link($info,"urlextern");
        }

        return  hsc($info['name']);
    }

    function make_link($info, $class) {
        return '<a href="'.$info['url'].'" title="'.$info['url'].'" class ="'.$class.'">'.hsc($info['name']).'</a>';
    }
}
