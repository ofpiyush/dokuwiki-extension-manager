<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array();
    var $filter_array('id','name','description','tag','type','author');
    var $result = array();
    var $repo = NULL;
    function process(){
        if(array_key_exists('term',$_REQUEST) && @strlen($_REQUEST['term']) > 0)
            $this->term = $_REQUEST['term'];
        if(array_key_exists('filters',$_REQUEST) && is_array($_REQUEST['filters']))
            $this->filters = $_REQUEST['filters'];
        if(!is_null($this->term)) {
            $this->repo = $this->repo_cache->retrieveCache();
            $this->lookup();
        }
    }
    
    function html() {
    }
    
    /**
     * Looks up the term in the repository cache according to filters set. Basic searching.
     * TODO advanced searching options (case-sensitive, for exact term etc) is it necessary??
     */
    protected function lookup() {
        if(!is_null($this->term)) {
            if(is_array($this->filters) && count($this->filters))
                $filters =array_intersect($this->filters,$this->filter_array)
            else
                $filters = $this->filter_array;
            $result = array();
            if(!is_null($this->repo)) {
                foreach($filters as $filter) {
                    foreach ($this->repo as $single) {
                        if($filter == 'tag') {
                            foreach($single['tags']['tag'] as $tag)
                                if(preg_match("/.*$keyword.*/ism",$tag))
                                    $tmp[$single['id']] = $single;
                        }
                        elseif(preg_match("/.*$keyword.*/ism",$single[$filter]))
                            $tmp[$single['id']] = $single;
                    }
                    $intersect = array_intersect_key($result,$tmp);
                    $result = array_diff_key($result, $tmp);
                    $result = array_merge($result,$tmp); 
                }
                return $this->result = $result;
            }
        }
    }
}
