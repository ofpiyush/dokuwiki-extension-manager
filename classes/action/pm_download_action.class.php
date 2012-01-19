<?php
/**
 * Download action class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Piyush Mishra <me@piyushmishra.com>
 */

class pm_download_action extends pm_base_action {

    var $is_url_download = false;
    var $downloaded = null;
    var $overwrite = true;

    /**
     * Initiate the plugin download
     */
    function act() {
        if(array_key_exists('url',$_REQUEST)) {
            $this->url_download();

        } elseif (is_array($this->selection)) {
            foreach ($this->selection as $cmdkey) {
                $info = $this->manager->info->get($cmdkey);
                $this->download_single($info);
            }
        }
        $this->refresh($this->manager->tab);
    }

    /**
     * Download using URL textbox
     */
    function url_download() {
        $this->is_url_download = true;
        $obj = new stdClass();
        $obj->downloadurl = $obj->id = $_REQUEST['url'];
        $this->download($obj, $this->overwrite,'abc');
    }

    /**
     * Overridable function to do download action on one url from repository
     */
    function download_single($info) {
        if (!$info->{'can_'.$this->manager->cmd}()) return;
        $default_type = ($info->is_template) ? 'template' : 'plugin';
        $this->download($info, $this->overwrite, $info->id, $default_type);
    }

    /**
     * Report action failed
     */
    function msg_failed($info, $error) {
        if ($this->is_url_download) {
            $this->report(-1, $info, 'url_failed', $error);
        } else {
            $this->report(-1, $info, 'download_failed', $error);
        }
    }

    /**
     * Report action succeeded
     */
    function msg_success($info) {
        $this->report(1, $info, 'download_success');
    }

    /**
     * Report action succeeded (more than one extension)
     */
    function msg_pkg_success($info,$components) {
        $this->report(1, $info, 'download_pkg_success',$components);
    }

    /**
     * Process the downloaded file
     */
    function download($info, $overwrite=false, $default_base = null, $default_type = null) {
        $error = null;
        $this->downloaded['plugin'] = array();
        $this->downloaded['template'] = array();

        // check the url
        $url = $info->downloadurl;
        $matches = array();
        if (!preg_match("/[^\/]*$/", $url, $matches) || !$matches[0]) {
            $this->msg_failed($info, $this->manager->getLang('error_badurl'));
            return false;
        }
        $file = $matches[0];

        // create tmp directory for download & decompress
        if (!($tmp = io_mktmpdir())) {
            $this->msg_failed($info, $this->manager->getLang('error_dircreate'));
            return false;
        }

        // add default base folder if specified to handle case where zip doesn't contain this
        if ($default_base) {
            if (!@mkdir("$tmp/$default_base")) {
                $this->msg_failed($info, $this->manager->getLang('error_dircreate'));
                return false;
            }
        }

        // download & decompress
        if (strpos($url, 'c:') === 0) {
            if (!@copy($url, "$tmp/".basename($url))) {
                $error = 'Failed to copy file '.$url.' -> '.$tmp;
            }
        } elseif (!$file = io_download($url, "$tmp/", true, $file)) {
            $error = sprintf($this->manager->getLang('error_download'),$url);
        }

        if (!$error && !$this->decompress("$tmp/$file", "$tmp/$default_base")) {
            $error = sprintf($this->manager->getLang('error_decompress'),$file);
        }

        // search $tmp/$default_base for the folder(s) that has been created
        // move the folder(s) to lib/..
        if (!$error) {
            $result = array('old'=>array(), 'new'=>array());

            if(!$this->find_folders($result,"$tmp/$default_base", $default_type)){
                $error = $this->manager->getLang('error_findfolder');

            } else {
                // choose correct result array
                if(count($result['new'])){
                    $install = $result['new'];
                }else{
                    $install = $result['old'];
                }

                // now install all found items
                foreach($install as $item){
                    // where to install?
                    if($item['type'] == 'template'){
                        $target_base_dir = DOKU_TPLLIB;
                    }else{
                        $target_base_dir = DOKU_PLUGIN;
                    }

                    if (!empty($item['base'])) {
                        // use base set in info.txt
                    } elseif ($item['type'] == 'template' && count($install) == 1) {
                        // safe to rename base for templates
                        $item['base'] = $info->id;
                    } else {
                        // default - use directory as found in zip
                        // plugins from github/master without *.info.txt will install in wrong folder
                        // but using $info->id will make 'code3' fail (which should install in lib/code/..)
                        $item['base'] = basename($item['tmp']);
                    }

                    // check to make sure we aren't overwriting anything
                    $target = $target_base_dir.$item['base'];
                    if (!$overwrite && @file_exists($target)) {
                        // TODO remember our settings, ask the user to confirm overwrite
                        continue;
                    }

                    $instruction = @file_exists($target) ? 'update' : 'install';

                    // copy action
                    if ($this->dircopy($item['tmp'], $target)) {
                        $this->downloaded[$item['type']][] = $item['base'];
                        $this->manager->log->write($target, $instruction, array('url' => $url, 'repokey' => $info->repokey));
                        $this->manager->tab = $item['type'];
                    } else {
                        $error = sprintf($this->manager->getLang('error_copy')."\n", $item['base']);
                        break;
                    }
                }
            }
        }

        // cleanup
        if ($tmp) $this->dir_delete($tmp);

        if ($error) {
            $this->msg_failed($info, $error);
            return false;
        }

        $downloaded = array_merge($this->downloaded['plugin'],$this->downloaded['template']);
        if (count($downloaded) > 1) {
            $this->msg_pkg_success($info, implode(',',$downloaded));
        } else {
            $this->msg_success($info);
        }
        return true;
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
     * @param string $default_type - type used if no info.txt available
     * @param string $dir - a subdirectory. do not set. used by recursion
     * @return bool - false on error
     */
    function find_folders(&$result,$base,$default_type,$dir=''){
        $this_dir = "$base$dir";
        $dh = @opendir($this_dir);
        if(!$dh) return false;

        $found_dirs = array();
        $found_files = 0;
        $found_template_parts = 0;
        $found_info_txt = false;
        while (false !== ($f = readdir($dh))) {
            if ($f == '.' || $f == '..') continue;

            if(is_dir("$this_dir/$f")) {
                $found_dirs[] = "$dir/$f";

            } else {
                // it's a file -> check for config
                $found_files++;
                switch ($f) {
                    case 'plugin.info.txt':
                    case 'template.info.txt':
                        $found_info_txt = true;
                        $info = array();
                        $type = explode('.',$f,2);
                        $info['type'] = $type[0];
                        $info['tmp']  = $this_dir;
                        $conf = confToHash("$this_dir/$f");
                        $info['base'] = basename($conf['base']);
                        $result['new'][] = $info;
                        break;

                    case 'main.php':
                    case 'details.php':
                    case 'mediamanager.php':
                    case 'style.ini':
                        $found_template_parts++;
                        break;
                }
            }
        }
        closedir($dh);

        // URL downloads default to 'plugin', try extra hard to indentify templates
        if (!$default_type && $found_template_parts > 2 && !$found_info_txt) {
            $info = array();
            $info['type'] = 'template';
            $info['tmp']  = $this_dir;
            $result['new'][] = $info;
        }

        // files in top level but no info.txt, assume this is zip missing a base directory
        // works for all downloads unless direct URL where $base will be the tmp directory ($info->id was empty)
        if (!$dir && $found_files > 0 && !$found_info_txt && $default_type) {
            $info = array();
            $info['type'] = $default_type;
            $info['tmp']  = $base;
            $result['old'][] = $info;
            return true;
        }

        foreach ($found_dirs as $found_dir) {
            // if top level add to dir list for old method, then recurse
            if(!$dir){
                $info = array();
                $info['type'] = ($default_type ? $default_type : 'plugin');
                $info['tmp']  = "$base$found_dir";
                $result['old'][] = $info;
            }
            $this->find_folders($result,$base,$default_type,"$found_dir");
        }
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
        if($conf['allowdebug']){
            msg("Decompress Error: Unsupported file type [$ext]",-1);
        }
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

