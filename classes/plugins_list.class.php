<?php
class plugins_list {

    protected $form = null;
    protected $actions = array();

    function __construct($id,$actions) {
        $this->actions[''] = '-Please Choose-';
        $this->actions = array_merge($this->actions,$actions);
        $this->form = new Doku_Form($id);
        $this->form->addHidden('page','plugin');
        $this->form->addHidden('fn','multiselect');
        $this->form->addElement(form_makeOpenTag('table',array('class'=>'inline')));
    }

    /**
     * Build single row of plugin form
     * @param array checkbox 
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

    function make_title($info) {
        if(array_key_exists('dokulink',$info) && strlen($info['dokulink'])) {
            $info['url'] = "http://dokuwiki.org/".$info['dokulink'];
            return '<a href="'.$info['url'].'" title="'.$info['url'].'" class ="interwiki iw_doku" >'.hsc($info['name']).'</a>';
        }
        if(array_key_exists('url',$info) && strlen($info['url'])) {
            return '<a href="'.$info['url'].'" title="'.$info['url'].'" class ="urlextern" >'.hsc($info['name']).'</a>';
        }
        return  hsc($info['name']);
    }
}
