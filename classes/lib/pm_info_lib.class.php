<?php
/**
 * Factory class to create information objects from plugin/tempalate id's
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_info_lib {

    function __construct(helper_plugin_extension $helper) {
        $this->helper = $helper;
    }

    /**
     * Create and return an info object
     * @property string type    'plugin'   - index is plugin folder name from plugin_list, used in plugin tab
     *                          'template' - index is template folder name from template_list, used in template tab
     *                          'search'   - index is repokey/folder (prefixed with 'template:' for tpl), used by all actions
     */
    function get($index,$type = 'search') {
        if(!in_array($type,array('plugin','template','search'))) {
            $type = 'plugin';
        }
        $is_installed = false;
        $is_template = false;
        $is_writable = false;
        list($repokey, $folder) = explode('/',$index,2);
        $id = $repokey;

        if ($type == 'search') {
            // assume plugin repo id (templates are prefixed by 'template:')
            if(stripos($repokey,'template:')===0) {
                $id = substr($repokey,9);
                $is_template = true;
                $is_writable = $this->helper->templatefolder_writable;
                if ($folder && in_array($folder,$this->helper->template_list)) {
                    $id = $folder;
                    $is_installed = true;
                    $type = 'template';
                }
            } else {
                $is_template = false;
                $is_writable = $this->helper->pluginfolder_writable;
                if ($folder && in_array($folder,$this->helper->plugin_list)) {
                    $id = $folder;
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
        $return = new $classname($this->helper,$id,$is_template);
        $return->is_installed = $is_installed;

        // don't assume extentions installed in correct directory (try read info.txt before repo searching)
        if ($is_installed) {
            $path = $return->install_directory();
            $is_writable = is_writable($path);

            $info_path = $path.$type.'.info.txt';
            $return->info = $this->read_info_txt($info_path);

            // only use getInfo fall-back for enabled plugins
            if(empty($return->info) && !$is_template && $return->is_enabled) {
                $return->info = $this->read_plugin_getInfo($id);
            }
            if(!empty($return->info['base'])) {
                $repokey = (($is_template) ? 'template:' : '').$return->info['base'];
            }
            $return->log = $this->helper->log->read($path);

            // installed plugins may have repokey stored from last download to handle case of 'code3' plugin in lib/code/.. 
            if (!empty($return->log['repokey'])) {
                $repokey = $return->log['repokey'];
            }
            $return->is_gitmanaged = file_exists($path.'.git');
        }

        $return->is_writable  = $is_writable;
        $return->repokey = $repokey;
        $return->repo = $this->find_repo_entry($return);

        // determine if there is reason to use repo data, check dokulink/saved repokey/used download url
        if (preg_match('/www.dokuwiki.org\/(\w+:[a-zA-Z\d_-]+)/', $return->info['url'], $match)) {
            if (str_replace('plugin:','',$match[1]) == $repokey) {
                $same_dokulink = true;
            }
        }
        if ($is_installed && !$return->log['repokey'] && $return->repo['downloadurl'] != $return->log['downloadurl'] && !$same_dokulink) {
            $return->repo = null;
        }
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
        if(!empty($this->helper->repo['data'][$return->repokey])) {
            return $this->helper->repo['data'][$return->repokey];
        }
        return false;
    }

    function read_plugin_getInfo($index) {
        $components = $this->helper->get_plugin_components($index);
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
