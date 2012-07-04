<?php
_cc::load_data_source('users_data');

class users_developers extends users_data {
    protected $_table = 'users_developers';
    protected $_fields = array( 
        'developer_id' => array( 
            'primary_key' => true, 
            'foreign_table' => 'users'
        ), 
        'developer_fname' => array( 
            'title' => 'Fname', 
            'type'  => 'text',
            'adjust_output' => '_simplest_filter' 
        ), 
        'developer_lname' => array( 
            'title' => 'Lname', 
            'type'  => 'text',
            'adjust_output' => '_simplest_filter' 
        ), 
    );
    protected $level = 1;
    protected $_prefix_fields = 'developer_';
}


