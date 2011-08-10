<?php
class ap_download extends ap_plugin {

    var $overwrite = true;
    var $downerrors = array();
    var $current = null;
    var $type = "plugin";

    /**
     * Initiate the plugin download
     */
    function process() {
        if(isset($_REQUEST['template'])) {
            $this->type ='template';
        }
        $this->down();
        $this->refresh($this->type);
        //failsafe for if refresh doesnt work
        parent::process();
    }

    function down() {
    //FIXME needs serious refactoring (probably after GSoC)
        if(array_key_exists('url',$_REQUEST)) {
            $plugin_url = $_REQUEST['url'];
            if($this->download($plugin_url, $this->overwrite)) {
                $base = $this->current['base'];
                if($this->current['type'] = "template")
                    msg(sprintf($this->get_lang('tempdownloaded'),$base),1);
                else
                   msg(sprintf($this->get_lang('downloaded'),$base),1);
            }
            else {
                msg($this->manager->error,-1);
            }
        }elseif(is_array($this->plugin) && count($this->plugin)) {
            $plugins = array_intersect_key($this->repo,array_flip($this->plugin));
            foreach ($plugins as $plugin) {
                $this->current = null;
                $this->manager->error = null;
                $type = (stripos($plugin['type'],'Template') !== false ) ? 'template' : 'plugin';
                $default_base = ($type == 'template') ? str_replace('template:','',$plugin['id']) :$plugin['id'];
                if($this->download($plugin['downloadurl'], $this->overwrite,$default_base,$type,$plugin)) {
                    $base = $this->current['base'];
                    if($this->current['type'] == 'template') {
                        msg(sprintf($this->get_lang('tempdownloaded'),$base),1);
                    } else {
                        msg(sprintf($this->get_lang('downloaded'),$base),1);
                    }
                } else {
                    msg(sprintf($this->get_lang('notdownloaded'),$plugin['id'])." <br />".$this->manager->error,-1);
                }
            }
        }
    }

    /**
     * Process the downloaded file
     */
    function download($url, $overwrite=false,$default_base = null, $default_type = "plugin",$plugin =array()) {
        global $lang;
        // check the url
        $matches = array();
        if (!preg_match("/[^\/]*$/", $url, $matches) || !$matches[0]) {
            $this->manager->error = $this->get_lang('error_badurl')."\n";
            return false;
        }

        $file = $matches[0];

        if (!($tmp = io_mktmpdir())) {
            $this->manager->error = $this->get_lang('error_dircreate')."\n";
            return false;
        }

        if (!$file = io_download($url, "$tmp/", true, $file)) {
            $this->manager->error = sprintf($this->get_lang('error_download'),$url)."\n";
        }

        if (!$this->manager->error && !$this->decompress("$tmp/$file", $tmp)) {
            $this->manager->error = sprintf($this->get_lang('error_decompress'),$file)."\n";
        }

        // search $tmp for the folder(s) that has been created
        // move the folder(s) to lib/plugins/
        if (!$this->manager->error) {
            $result = array('old'=>array(), 'new'=>array());
            if($this->find_folders($result,$tmp,'', $default_type)){
                // choose correct result array
                if(count($result['new'])){
                    $install = $result['new'];
                }else{
                    $install = $result['old'];
                }
                // now install all found items
                foreach($install as $item){
                    $this->current = $item;
                    // where to install?
                    if($item['type'] == 'template'){
                        $target_base_dir = DOKU_INC.'lib/tpl/';
                        if(!empty($default_base) && !file_exists($item['tmp'].'/template.info.txt'))
                            $item['base'] = $default_base;
                    }else{
                        $target_base_dir = DOKU_INC.'lib/plugins/';
                        if(!empty($default_base) && !file_exists($item['tmp'].'/plugin.info.txt'))
                            $item['base'] = $default_base;
                    }
                    $target = $target_base_dir.$item['base'];
                    // check to make sure we aren't overwriting anything
                    if (!$overwrite && @file_exists($target)) {
                        // remember our settings, ask the user to confirm overwrite, FIXME
                        continue;
                    }

                    $instruction = @file_exists($target) ? 'update' : 'install';

                    // copy action
                    if ($this->dircopy($item['tmp'], $target)) {
                        $this->downloaded[$item['type']][] = $item['base'];
                        $version = '';
                        if(!empty($plugin['lastupdate']))
                            $version = date('Y-m-d',strtotime($plugin['lastupdate']));
                        $this->plugin_writelog($target, $instruction, array('url' =>$url,'version'=>$version));
                    } else {
                        $this->manager->error .= sprintf($this->get_lang('error_copy')."\n", $item['base']);
                    }
                }

            } else {
                $this->manager->error = $this->get_lang('error')."\n";
            }
        }

        // cleanup
        if ($tmp) $this->dir_delete($tmp);

        if (!$this->manager->error) {
            return true;
        }

        return false;
    }

    /**
     * Find out what was in the extracted directory
     *
     * Correct folders are searched recursively using the "*.info.txt" configs
     * as indicator for a root folder. When such a file is found, it's base
     * setting is used (when set). All folders found by this method are stored
     * in the 'new' key of the $result array.
     *
     * For backwards compatibility all found top level folders are stored as
     * in the 'old' key of the $result array.
     *
     * When no items are found in 'new' the copy mechanism should fall back
     * the 'old' list.
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param arrayref $result - results are stored here
     * @param string $base - the temp directory where the package was unpacked to
     * @param string $dir - a subdirectory. do not set. used by recursion
     * @return bool - false on error
     */
    function find_folders(&$result,$base,$dir='',$default_type ="plugin"){
        $dh = @opendir("$base/$dir");
        if(!$dh) return false;
        while (false !== ($f = readdir($dh))) {
            if ($f == '.' || $f == '..' || $f == 'tmp') continue;

            if(!is_dir("$base/$dir/$f")){
                // it's a file -> check for config
                if($f == 'plugin.info.txt'){
                    $info = array();
                    $info['type'] = 'plugin';
                    $info['tmp']  = "$base/$dir";
                    $conf = confToHash("$base/$dir/$f");
                    $info['base'] = basename($conf['base']);
                    if(!$info['base']) $info['base'] = basename("$base/$dir");
                    $result['new'][] = $info;
                } elseif($f == 'template.info.txt') {
                    $info = array();
                    $info['type'] = 'template';
                    $info['tmp']  = "$base/$dir";
                    $conf = confToHash("$base/$dir/$f");
                    $info['base'] = basename($conf['base']);
                    if(!$info['base']) $info['base'] = basename("$base/$dir");
                    $result['new'][] = $info;
                } elseif($f == 'main.php' || stripos($f,'style.ini')!==false) {
                    $tempid = basename("$base/$dir");
                    if(isset($this->repo['template:'.$tempid])) {
                        $info = array();
                        $info['type'] = 'template';
                        $info['tmp']  = "$base/$dir";
                        $info['base'] = $tempid;
                        $result['new'][] = $info;
                    }
                }
            }else{
                // it's a directory -> add to dir list for old method, then recurse
                if(!$dir){
                    $info = array();
                    $info['type'] = $default_type;
                    $info['tmp']  = "$base/$dir/$f";
                    $info['base'] = $f;
                    $result['old'][] = $info;
                }
                $this->find_folders($result,$base,"$dir/$f",$default_type);
            }
        }
        closedir($dh);
        return true;
    }


    /**
     * Decompress a given file to the given target directory
     *
     * Determines the compression type from the file extension
     */
    function decompress($file, $target) {
        global $conf;

        // decompression library doesn't like target folders ending in "/"
        if (substr($target, -1) == "/") $target = substr($target, 0, -1);

        $ext = $this->guess_archive($file);
        if (in_array($ext, array('tar','bz','gz'))) {
            switch($ext){
                case 'bz':
                    $compress_type = TarLib::COMPRESS_BZIP;
                    break;
                case 'gz':
                    $compress_type = TarLib::COMPRESS_GZIP;
                    break;
                default:
                    $compress_type = TarLib::COMPRESS_NONE;
            }

            $tar = new TarLib($file, $compress_type);
            if($tar->_initerror < 0){
                if($conf['allowdebug']){
                    msg('TarLib Error: '.$tar->TarErrorStr($tar->_initerror),-1);
                }
                return false;
            }
            $ok = $tar->Extract(TarLib::FULL_ARCHIVE, $target, '', 0777);

            if($ok<1){
                if($conf['allowdebug']){
                    msg('TarLib Error: '.$tar->TarErrorStr($ok),-1);
                }
                return false;
            }
            return true;
        } else if ($ext == 'zip') {

            $zip = new ZipLib();
            $ok = $zip->Extract($file, $target);

            // FIXME sort something out for handling zip error messages meaningfully
            return ($ok==-1?false:true);

        }

        // unsupported file type
        return false;
    }

    /**
     * Determine the archive type of the given file
     *
     * Reads the first magic bytes of the given file for content type guessing,
     * if neither bz, gz or zip are recognized, tar is assumed.
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @returns false if the file can't be read, otherwise an "extension"
     */
    function guess_archive($file){
        $fh = fopen($file,'rb');
        if(!$fh) return false;
        $magic = fread($fh,5);
        fclose($fh);

        if(strpos($magic,"\x42\x5a") === 0) return 'bz';
        if(strpos($magic,"\x1f\x8b") === 0) return 'gz';
        if(strpos($magic,"\x50\x4b\x03\x04") === 0) return 'zip';
        return 'tar';
    }

    /**
     * Copy with recursive sub-directory support
     */
    function dircopy($src, $dst) {
        global $conf;

        if (is_dir($src)) {
            if (!$dh = @opendir($src)) return false;

            if ($ok = io_mkdir_p($dst)) {
                while ($ok && (false !== ($f = readdir($dh)))) {
                    if ($f == '..' || $f == '.') continue;
                    $ok = $this->dircopy("$src/$f", "$dst/$f");
                }
            }

            closedir($dh);
            return $ok;

        } else {
            $exists = @file_exists($dst);

            if (!@copy($src,$dst)) return false;
            if (!$exists && !empty($conf['fperm'])) chmod($dst, $conf['fperm']);
            @touch($dst,filemtime($src));
        }

        return true;
    }


}

