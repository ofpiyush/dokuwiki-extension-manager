<?php
class pm_log_lib {
    var $log =array();
    /**
     * Write a log entry to the given target directory
     */
    function write($target, $cmd, $data, $date = true) {
        $file  = $target.'/manager.dat';
        $out   = '';
        $write = false;
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $out .= "$key=$value".PHP_EOL;
            }
        }
        if($cmd == 'install') {
            if($date)
                $out .= "installed=".date('r').PHP_EOL;
            if(!$fp = @fopen($file, 'wb')) return false;
            $write = fwrite($fp, $out);
            fclose($fp);
        } elseif($cmd == 'update') {
            if($date)
                $out .= "updated=".date('r').PHP_EOL;
            if (!$fp = @fopen($file, 'a')) return false;
            $write = fwrite($fp, $out);
            fclose($fp);
        } else {
            return false;
        }
        return $write;
    }

    function read($path, $field = 'ALL') {
        $hash = md5($path);

        if (!isset($this->log[$hash])) {
            $file = @file($path.'manager.dat');
            if(empty($file)) return false;
            foreach($file as $line) {
                list($key, $value) = explode('=', trim($line, PHP_EOL), 2);
                $key = trim($key);
                $value = trim($value);
                // backwards compatible with old plugin manager
                if($key == 'url') $key = 'downloadurl';
                $this->log[$hash][$key] = $value;
            }
        }

        if ($field == 'ALL') {
            return $this->log[$hash];
        }

        if(!empty($this->log[$hash][$field])) return $this->log[$hash][$field];
        return false;
    }

    /**
     * log activity to a common log file
     */
    function trace($extension, $msg) {
        $file = DOKU_PLUGIN.'extension/trace.log';
        $out = sprintf("%s  %s  %-25s  %s \n", date('Y-m-d  H:i:s'), $_SERVER['REMOTE_USER'], $extension, $msg);
        if (!$fp = @fopen($file, 'a')) return false;
        $write = fwrite($fp, $out);
        fclose($fp);
        return $write;
    }
}
