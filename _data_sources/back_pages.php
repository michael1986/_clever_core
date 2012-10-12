<?php
_cc::load_data_source('db_table_tree');

class back_pages extends db_table_tree {
    protected $_table = 'back_pages';
    protected $_fields = array( 
        'page_id' => array( 
            'primary_key' => true, 
            'auto_increment' => true,
        ), 
        'page_parent_id' => array( 
            'foreign_table' => 'back_pages', 
            'title' => 'Parent id', 
            'type'  => 'select_foreign_tree',
            'zero_option' => '- root -',
            'values' => 'page_id',
            'labels' => 'page_title',
            'adjust_output' => '_simplest_filter' 
        ), 
        'page_title' => array( 
            'title' => 'Title', 
            'type'  => 'text',
            'adjust_output' => '_simplest_filter' 
        ), 
        'page_access_type' => array( 
            'title' => 'Access type', 
            'type'  => 'select',
            'values' => array('include', 'exclude')
        ), 
        'page_access_level' => array( 
            'title' => 'Access level', 
            'type'  => 'select_foreign_list',
            'foreign_table' => 'user_access_levels',
            'values' => 'al_level',
            'labels' => 'al_title',
            'multiple' => true
        ), 
        'page_module' => array( 
            'title' => 'Module', 
            'type'  => 'text',
            'adjust_output' => '_simplest_filter' 
        ), 
        'page_sort' => array( 
            'sort' => true, 
        ), 
    );
    protected $_prefix_fields = 'page_';
    protected $is_initialized_pages = false;
    protected $is_initialized_pages_univariate = false;
    protected $pages = false;

    public function get_pages() {
        $this->initialize_pages();
        return $this->pages;
    }

    public function get_pages_univariate() {
        $this->initialize_pages_univariate();
        return $this->pages_univariate;
    }

    protected function initialize_pages() {
        if (!$this->is_initialized_pages) {
            $this->pages = $this->build_pages_tree(
                $this->
                    _order('page_parent_id, page_sort')->
                    _arows()
            );
            $this->is_initialized_pages = true;
        }
    }

    protected function build_pages_tree($pages, $parent_id = 0, $deep = 0) {
        $started = false;
        $ret = array();
        $parent_aliases = array();
        foreach ($pages as $p) {
            if ($p['page_parent_id'] == $parent_id) {
                $p['deep'] = $deep;

                $ret[$p['page_id']] = $p;
                $ret[$p['page_id']]['pages'] = $this->build_pages_tree($pages, $p['page_id'], $deep + 1);

                $started = true;
            }
            else if ($started) {
                break;
            }
            
        }
        return $ret;
    }

    protected function initialize_pages_univariate() {
        if (!$this->is_initialized_pages_univariate) {
            $this->initialize_pages();
            list($this->pages_univariate, $this->pages_univariate_ids) = $this->get_pages_univariate_recur($this->pages);

            $this->pages_univariate_ids = array();
            for ($i = 0; $i < sizeof($this->pages_univariate); $i++) {
                $this->pages_univariate_ids[$this->pages_univariate[$i]['page_id']] = $i;
            }

            $this->is_initialized_pages_univariate = true;
        }
    }

    protected function get_pages_univariate_recur($pages, $pages_ids_displacement = 0) {
        $ret_pages = array();
        $ret_pages_ids = array();
        foreach ($pages as $p) {
            if (isset($p['pages'])) {
                $deeg_pages = $p['pages'];
                unset($p['pages']);
            }
            else {
                $deeg_pages = false;
            }
            $ret_pages[] = $p;
            $ret_pages_ids[$p['page_id']] = $pages_ids_displacement;
            $pages_ids_displacement++;

            if ($deeg_pages) {
                list($pages_tmp, $pages_ids_tmp) = $this->get_pages_univariate_recur($deeg_pages, $pages_ids_displacement);
                $ret_pages = array_merge($ret_pages, $pages_tmp);
                // to preserve keys use + instead of array_merge
                $ret_pages_ids += $pages_ids_tmp;
                $pages_ids_displacement += sizeof($pages_ids_tmp);
            }
        }
        return array($ret_pages, $ret_pages_ids);
    }

    public function find_page_with_content_inside_page_data($page_data, $user_level) {
        return $this->find_page_with_content_inside_page_data_recur($page_data, $user_level);
    }

    protected function find_page_with_content_inside_page_data_recur($page_data, $user_level) {
        if ($this->check_page_access($page_data, $user_level)) {
            if ($page_data['page_module']) {
                return $page_data;
            }
            else if (isset($page_data['pages'])) {
                $pages = $page_data['pages'];
            }
            else {
                $pages = $this->get_subpages($page_data);
            }
            foreach ($pages as $p) {
                $data = $this->find_page_with_content_inside_page_data_recur($p, $user_level);
                if ($data !== false) {
                    return $data;
                }
            }
        }
        return false;
    }

    public function get_page_data_from_id($page_id) {
        $this->initialize_pages_univariate();
        $page_data = $this->pages_univariate[$this->pages_univariate_ids[$page_id]];
        return $page_data;
    }

    public function get_subpages($page_data) {
        $this->initialize_pages_univariate();

        $page_parent_ids = array($page_data['page_id']);
        $tmp_page_data = $page_data;
        while ($tmp_page_data['page_parent_id']) {
            array_unshift($page_parent_ids, $tmp_page_data['page_parent_id']);
            $tmp_page_data = $this->pages_univariate[$this->pages_univariate_ids[$tmp_page_data['page_parent_id']]];
        }
        $pages = $this->pages;
        do {
            $pages = $pages[array_shift($page_parent_ids)]['pages'];
        } while(sizeof($page_parent_ids));

        return $pages;
    }

    public function check_page_access($description, $user_level) {
        $page_access_level = _array_implode_math('|', $description['page_access_level']);
        if (
            (
                $description['page_access_type'] == 'exclude' &&
                !((int)$page_access_level & (int)$user_level)
            ) || 
            (
                (int)$page_access_level & (int)$user_level
            )
        ) {
            return true;
        }
        else {
            return false;
        }
    }

    public function _adjust_db_input($in) {
        if (isset($in['page_access_level'])) {
            $in['page_access_level'] = _array_implode_math('|', $in['page_access_level']);
        }
        return $in;
    }

    public function _adjust_db_output($in, $mode = false) {
        if (isset($in['page_access_level'])) {
            $in['page_access_level'] = _get_bits($in['page_access_level']);
        }
        return $in;
    }

}


