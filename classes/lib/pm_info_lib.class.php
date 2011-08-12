<?php
class pm_info_lib {

    function __construct(admin_plugin_plugin $manager) {
        $this->m = $manager;
    }
//TODO split into the three child classes (too many if in here :()
    function get($index,$type = "plugin") {
        global $conf, $plugin_protected;
        $info_autogen =false;
        if(!in_array($type,array('plugin','template','search'))) {
            $type = 'plugin';
        }
        $classname = "pm_".$type."_single_lib";
        $return = new $classname($this->m,$index);
        if($type =="search") {
            $return->repo = $this->m->repo[$index];
            if(stripos($index,'template:')===0) {
                $return->is_writable = is_writable(DOKU_INC."lib/tpl/");
                $return->is_template = true;
            } else {
                $return->is_writable = is_writable(DOKU_PLUGIN);
                $return->is_template = false;
            }
            return $return;
        }
        $path = ($type == "plugin") ? DOKU_PLUGIN.plugin_directory($index).'/': DOKU_INC."lib/tpl/$index/";
        $return->is_writable = is_writable($path);
        $info_path = $path.$type.'.info.txt';//full path to *.info.txt

        $return->info = $this->setup_info($info_path);
        if(empty($return->info) && $return->is_writable) {
            if($type = 'plugin') {
                //fetch and save the info.txt for faster future loads
                $new = $this->comptoinfo($index);
                if(!empty($new)) {
                    $return->info = $new;
                    if(empty($new['base'])) $new['base'] = $index;
                    $this->info_autogen($info_path,(object) $new);
                }
            } else {
                //its a template. lets see if we can get it to autogen from the repo
                $info_autogen = true;
            }
        }
        $return->manager = $this->m->log->read($path);
        $return->repo = $this->repotoinfo($return,$index,$type);
        $this->check_dlurlchange($return->repo,$return->log,$path);
        if($type =="plugin") {
            $cascade = plugin_getcascade();
            if(!empty($cascade['protected'])) {
                $protected = array_merge(array_keys($cascade['protected']),$plugin_protected);
            } else {
                $protected = $plugin_protected;
            }
            $return->is_protected = in_array($return->id,$protected);
            $return->is_bundled = in_array($return->id,$this->m->_bundled);
            $return->is_enabled = !plugin_isdisabled($return->id);
            $return->is_template = false;
        } else {
            $return->is_bundled = ($return->id == 'default');
            $return->is_protected = in_array($return->id, array('default',$conf['template']));
            $return->is_enabled = ($return->id == $conf['template']);
            $return->is_template = true;
        }
        
        if($info_autogenerate && !empty($return->description)) {
            $this->info_autogen($info_path,$return);
        }
        return $return;
    }

    function check_dlurlchange($return,$log,$path) {
        if(!empty($return['downloadurl'])  && !empty($log['downloadurl']) &&
                $return['downloadurl'] != $log['downloadurl']) {
            if($this->plugin_writelog($path,'update',array('url'=>$return['downloadurl']),false)) {
                msg(sprintf($this->m->getLang('url_change'),hsc($return['id']),hsc($return['downloadurl']),hsc($log['downloadurl']),hsc($return['name']),$this->m->getLang('btn_info'),
                    $this->m->getLang('source'),hsc($path)),2);
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
        if(!empty($return->manager['repoid'])) {
            $repo_key = $return->manager['repoid'];
        } elseif(!empty($return->info['base'])) {
            $repo_key = $return->info['base'];
        } else {
            $repo_key = $index;
        }
        if($type == "template")
            $repo_key = "template:".$repo_key;
        if(!empty($this->m->repo[$repo_key])) {
            return $this->m->repo[$repo_key];
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
    function info_autogen($file,$return) {
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
        msg(sprintf($this->m->getLang('autogen_info'),$return->base),2);
        return true;
    }

}
