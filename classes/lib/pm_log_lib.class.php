<?php
class pm_log_lib {
    var $log =array();
    /**
     * Write a log entry to the given target directory
     */
    function write($target, $cmd, $data,$date = true) {
        $file = $target.'/manager.dat';
        $out = "";
        $write =false;
        if(!empty($data['url'])) {
            $out = "downloadurl=".$data['url'].PHP_EOL;
        }
        if(!empty($data['pm_date_version'])) {
            $out .= "pm_date_version=".$data['pm_date_version'].PHP_EOL;
        }
        if(!empty($data['repoid'])) {
            $out .= "repoid=".$data['repoid'].PHP_EOL;
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

    function read($path,$field = 'ALL') {
        $hash = md5($path);

        if (!isset($this->log[$hash])) {
            $file = @file($path.'manager.dat');
            if(empty($file)) return false;
            foreach($file as $line) {
                $line = explode('=',trim($line,PHP_EOL));
                $line = array_map('trim', $line);
                if($line[0] == 'url') $line[0] = 'downloadurl';
                $this->log[$hash][$line[0]] = $line[1];
            }
        }

        if ($field == 'ALL') {
            return $this->log[$hash];
        }

        if(!empty($this->log[$hash][$field])) return $this->log[$hash][$field];
        return false;
    }
}
