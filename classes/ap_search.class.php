<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array();
    var $filter_array = array('id','name','description','tag','type','author');
    var $result = array();
    var $repo = NULL;
    function process() {
        if(array_key_exists('term',$_REQUEST) && @strlen($_REQUEST['term']) > 0)
            $this->term = $_REQUEST['term'];
        if(array_key_exists('filters',$_REQUEST) && is_array($_REQUEST['filters']))
            $this->filters = $_REQUEST['filters'];
        if(!is_null($this->term)) {
            $this->repo = unserialize($this->repo_cache->retrieveCache());
            $this->lookup();
        }
    }
    
    function html() {
        if(is_array($this->result) && count($this->result)) {
            //ptln('<pre>');
            //print_r($this->result);
            //ptln('</pre>');
        }
        parent::html();
    }
    
    /**
     * Looks up the term in the repository cache according to filters set. Basic searching.
     * TODO advanced searching options (case-sensitive, for exact term etc) is it necessary??
     */
    protected function lookup() {
        if(!is_null($this->term)) {
            if(is_array($this->filters) && count($this->filters))
                $filters =array_intersect($this->filters,$this->filter_array);
            else
                $filters = $this->filter_array;
            $result = array();
            $tmp=array();
            if(!is_null($this->repo)) {
                foreach($filters as $filter) {
                    foreach ($this->repo as $index=> $single) {
                        if($filter == 'tag') {
                            if(is_array($single['tags'])) {
                                if(is_array($single['tags']['tag'])) {
                                    foreach($single['tags']['tag'] as $tag)
                                        if(preg_match("/.*$this->term.*/ism",$tag))
                                            $tmp[$single['id']] = $single;
                                }
                                else {
                                     if(preg_match("/.*$this->term.*/ism",$single['tags']['tag']))
                                        $tmp[$single['id']] = $single;
                                }
                            }
                        }
                        elseif(preg_match("/.*$this->term.*/ism",$single[$filter]))
                            $tmp[$single['id']] = $single;
                        $intersect = array_intersect_key($result,$tmp);
                        $result = array_diff_key($tmp, $result);
                        $result = array_merge($intersect,$result);
                    }
                }
                return $this->result = $result;
            }
        }
    }
}
