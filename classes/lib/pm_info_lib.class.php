<?php
class pm_info_lib {

    function __construct(admin_plugin_plugin $manager) {
        $this->manager = $manager;
    }
//TODO split into the three child classes (too many if in here :()
    function get($index,$type = "plugin") {
        $info_autogen =false;
        if(!in_array($type,array('plugin','template','search'))) {
            $type = 'plugin';
        }
        $classname = "pm_".$type."_single_lib";
        $return = new $classname($this->manager,$index);
        if($type =="search") {
            $return->repo = $this->manager->repo[$index];
            if(stripos($index,'template:')===0) {
                $return->is_writable = is_writable(DOKU_INC."lib/tpl/");
                $return->is_template = true;
            } else {
                $return->is_writable = is_writable(DOKU_PLUGIN);
                $return->is_template = false;
            }
            $this->setup_definers($return);
            return $return;
        }
        $path = ($type == "plugin") ? DOKU_PLUGIN.plugin_directory($index).'/': DOKU_INC."lib/tpl/$index/";
        $return->is_writable = is_writable($path);
        $info_path = $path.$type.'.info.txt';//full path to *.info.txt

        $return->info = $this->setup_info($info_path);
        if(empty($return->info) && $return->is_writable) {
            if($type == 'plugin') {
                //fetch and save the info.txt for faster future loads
                $new = $this->comptoinfo($index);
                if(!empty($new)) {
                    $return->info = $new;
                    $this->info_autogen($info_path,(object) $new,$index);
                }
            } else {
                //its a template. lets see if we can get it to autogen from the repo
                $info_autogen = true;
            }
        }
        $return->log = $this->manager->log->read($path);
        $return->repo = $this->repotoinfo($return,$index,$type);
        $this->check_dlurlchange($return->repo,$return->log,$path);
        $return->is_template = ($type == "template");
        $this->setup_definers($return);
        if($info_autogen && !empty($return->description)) {
            $this->info_autogen($info_path,$return,$index);
        }
        return $return;
    }
    /**
     * sets up some  is_* definers
     */
    function setup_definers($return) {
        global $plugin_protected,$conf;
        if($return->is_template) {
            $id = str_replace('template:','',$return->id);
            $return->is_bundled = ($id == 'default');
            $return->is_protected = in_array($id, array('default',$conf['template']));
            $return->is_installed = in_array($id,$this->manager->template_list);
            $return->is_enabled = ($id == $conf['template']);
        } else {
            $cascade = plugin_getcascade();
            if(!empty($cascade['protected'])) {
                $protected = array_merge(array_keys($cascade['protected']),$plugin_protected);
            } else {
                $protected = $plugin_protected;
            }
            $return->is_installed = in_array($return->id,$this->manager->plugin_list);
            $return->is_protected = in_array($return->id,$protected);
            $return->is_bundled = in_array($return->id,$this->manager->_bundled);
            $return->is_enabled = !plugin_isdisabled($return->id);
        }
    }
    function check_dlurlchange($return,$log,$path) {
        if(!empty($return['downloadurl'])  && !empty($log['downloadurl']) &&
                $return['downloadurl'] != $log['downloadurl']) {
            if($this->manager->log->write($path,'update',array('url'=>$return['downloadurl']),false)) {
                msg(sprintf($this->manager->getLang('url_change'),hsc($return['id']),hsc($return['downloadurl']),hsc($log['downloadurl']),hsc($return['name']),$this->manager->getLang('btn_info'),
                    $this->manager->getLang('source'),hsc($path)),2);
            }
        }
    }
    function setup_info($info_path) {
        if(@file_exists($info_path)) {
            $file_info = confToHash($info_path);
            if(!empty($file_info)) {
                return $this->clean_info($file_info);
            }
        }
        return false;
    }

    function repotoinfo($return,$index,$type) {
        if(!empty($return->log['repoid'])) {
            $repo_key = $return->log['repoid'];
        } elseif(!empty($return->info['base'])) {
            $repo_key = $return->info['base'];
        } else {
            $repo_key = $index;
        }
        if($type == "template")
            $repo_key = "template:".$repo_key;
        if(!empty($this->manager->repo[$repo_key])) {
            return $this->manager->repo[$repo_key];
        }
        return false;
    }

    function comptoinfo($index) {
        $components = get_plugin_components($index);
        if(!empty($components)) {
            $obj = plugin_load($components[0]['type'],$components[0]['name'],false,true);
            if(!empty($obj)) {
                $obj_info = $obj->getInfo();
                return  $this->clean_info($obj_info);
            }
        }
    }

    function clean_info($raw_info) {
        $info = array(
            'base'  => false,
            'author'=> false,
            'email' => false,
            'date'  => false,
            'name'  => false,
            'desc'  => false,
            'url'   => false
            );
        if(is_array($raw_info))
            return array_intersect_key($raw_info,$info);
        return false;
    }

    /**
     * Auto generate plugin and template info.txt
     */
    function info_autogen($file,$return,$folder) {
        $info = "";
        foreach(array('base','author','email','date','name','desc','url') as $index) {
            if(!empty($return->$index)) $info.= $index." ".$return->$index."\n";
        }
        if(empty($return->desc) && !empty($return->description)) {
            $info.='desc '.$return->description."\n";
        }
        if(empty($return->url) && !empty($return->dokulink)) {
            $info.='url http://www.dokuwiki.org/'.$return->dokulink."\n";
        }
        if($info == "") return false;
        if (!$fp = @fopen($file, 'w')) return false;
        fwrite($fp, $info);
        fclose($fp);
        msg(sprintf($this->manager->getLang('autogen_info'),$folder),2);
        return true;
    }

}
