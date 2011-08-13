<?php
class pm_download_action extends pm_base_action {

    var $overwrite = true;
    var $downerrors = array();
    var $current = null;
    var $templated =false;
    var $plugined = false;

    /**
     * Initiate the plugin download
     */
    function act() {
        if(isset($_REQUEST['template'])) {
            $this->type ='template';
        }
        $this->down();
        if($this->m->tab == "search") {
            if($this->templated && !$this->plugined)
                $this->m->tab = 'template';
            else
                $this->m->tab = 'plugin';
        }
        $this->refresh($this->m->tab);
    }

    function down() {
        if(array_key_exists('url',$_REQUEST)) {
            $obj = new stdClass();
            $obj->downloadurl = $_REQUEST['url'];
            if($this->download($obj, $this->overwrite)) {
                $base = $this->current['base'];
                if($this->current['type'] = "template")
                    msg(sprintf($this->m->getLang('tempdownloaded'),$base),1);
                else
                   msg(sprintf($this->m->getLang('downloaded'),$base),1);
            }
            else {
                msg($this->m->error,-1);
            }
        }elseif(is_array($this->plugin) && count($this->plugin)) {
            foreach ($this->plugin as $plugin) {
                if(array_key_exists($plugin,$this->m->repo)) {
                    $info = $this->m->info->get($plugin,'search');
                    if($info->can_download()) {
                        $this->current = null;
                        $this->m->error = null;
                        $type = ($info->is_template) ? 'template' : 'plugin';
                        $default_base = ($info->is_template) ? str_replace('template:','',$info->id) :'';
                        if($this->download($info, $this->overwrite,$default_base,$type)) {
                            $base = $this->current['base'];
                            if($this->current['type'] == 'template') {
                                $this->templated = true;
                                msg(sprintf($this->m->getLang('tempdownloaded'),$base),1);
                            } else {
                                $this->plugined = true;
                                msg(sprintf($this->m->getLang('downloaded'),$base),1);
                            }
                        } else {
                            msg(sprintf($this->m->getLang('notdownloaded'),$plugin['id'])." <br />".$this->m->error,-1);
                        }
                    }
                }
            }
        }
    }

    /**
     * Process the downloaded file
     */
    function download($plugin , $overwrite=false,$default_base = null, $default_type = "plugin") {
        global $lang;
        $url = $plugin->downloadurl;
        // check the url
        $matches = array();
        if (!preg_match("/[^\/]*$/", $url, $matches) || !$matches[0]) {
            $this->m->error = $this->m->getLang('error_badurl')."\n";
            return false;
        }

        $file = $matches[0];

        if (!($tmp = io_mktmpdir())) {
            $this->m->error = $this->m->getLang('error_dircreate')."\n";
            return false;
        }

        if (!$file = io_download($url, "$tmp/", true, $file)) {
            $this->m->error = sprintf($this->m->getLang('error_download'),$url)."\n";
        }

        if (!$this->m->error && !$this->decompress("$tmp/$file", $tmp)) {
            $this->m->error = sprintf($this->m->getLang('error_decompress'),$file)."\n";
        }

        // search $tmp for the folder(s) that has been created
        // move the folder(s) to lib/plugins/
        if (!$this->m->error) {
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
                        $version = $repoid = '';
                        if(!empty($plugin->lastupdate))
                            $version = $plugin->lastupdate;
                        if(!empty($default_base) && !file_exists($target.'/'.$item['type'].'.info.txt'))
                            $repoid = $default_base;
                        $this->m->log->write($target, $instruction, array('url' =>$url,'repoid'=>$repoid,'pm_date_version'=>$version));
                    } else {
                        $this->m->error .= sprintf($this->m->getLang('error_copy')."\n", $item['base']);
                    }
                }

            } else {
                $this->m->error = $this->m->getLang('error')."\n";
            }
        }

        // cleanup
        if ($tmp) $this->dir_delete($tmp);

        if (!$this->m->error) {
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

