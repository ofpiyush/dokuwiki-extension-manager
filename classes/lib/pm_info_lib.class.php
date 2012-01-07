<?php
/**
 * Factory class to create information objects from plugin/tempalate id's
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_info_lib {

    function __construct(admin_plugin_extension $manager) {
        $this->manager = $manager;
    }

    function get($index,$type = 'search') {
        if(!in_array($type,array('plugin','template','search'))) {
            $type = 'plugin';
        }

        $is_installed = false;
        $is_template = false;
        $is_writable = false;
        $repokey = $index;
        $id = $index;

        if ($type == 'search') {
            // assume plugin repo id (templates are prefixed by 'template:')
            $id = str_replace('template:','',$index);
            if(stripos($index,'template:')===0) {
                $is_template = true;
                $is_writable = $this->manager->templatefolder_writable;
                if (in_array($id,$this->manager->template_list)) {
                    $is_installed = true;
                    $type = 'template';
                }
            } else {
                $is_template = false;
                $is_writable = $this->manager->pluginfolder_writable;
                if (in_array($id,$this->manager->plugin_list)) {
                    $is_installed = true;
                    $type = 'plugin';
                }
            }
        } else {
            // assume this call was generated from installed plugins/templates list
            $is_installed = true;
            $is_template = ($type == 'template');
            $repokey = (($is_template) ? 'template:'.$index : $index);
        }

        $classname = "pm_".$type."_single_lib";
        $return = new $classname($this->manager,$id,$is_template);
        $return->is_installed = $is_installed;
        $return->is_writable  = $is_writable;

        // don't assume extentions installed in correct directory (try read info.txt before repo searching)
        if ($is_installed) {
            $path = $return->install_directory();
            $return->is_writable = is_writable($path);

            $info_path = $path.$type.'.info.txt';
            $return->info = $this->read_info_txt($info_path);

            // only use getInfo fall-back for enabled plugins
            if(empty($return->info) && !$is_template && $return->is_enabled) {
                $return->info = $this->read_plugin_getInfo($index);
            }
            if(!empty($return->info['base'])) {
                $repokey = (($is_template) ? 'template:' : '').$return->info['base'];
            }
            $return->log = $this->manager->log->read($path);
            $return->is_gitmanaged = file_exists($path.'.git');
        }

        $return->repokey = $repokey;
        $return->repo = $this->find_repo_entry($return);
        return $return;
    }

    function read_info_txt($info_path) {
        if(@file_exists($info_path)) {
            $file_info = confToHash($info_path);
            if(!empty($file_info)) {
                return $this->clean_info($file_info);
            }
        }
        return false;
    }

    function find_repo_entry($return) {
        if(!empty($this->manager->repo[$return->repokey])) {
            return $this->manager->repo[$return->repokey];
        }
        return false;
    }

    function read_plugin_getInfo($index) {
        $components = $this->manager->get_plugin_components($index);
        if(!empty($components)) {
            $obj = plugin_load($components[0]['type'],$components[0]['name'],false,true);
            if(is_object($obj) && method_exists($obj,'getInfo') ) {
                $obj_info = $obj->getInfo();
                return  $this->clean_info($obj_info);
            }
        }
    }

    function clean_info($raw_info) {
        if(!is_array($raw_info)) return false;
        $info = array(
            'base'  => false,
            'author'=> false,
            'email' => false,
            'date'  => false,
            'name'  => false,
            'desc'  => false,
            'url'   => false
            );
        return array_intersect_key($raw_info,$info);
    }

}
