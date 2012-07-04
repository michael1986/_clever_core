<?php
_cc::load_data_source('users_data');

class users_admins extends users_data {
    protected $_table = 'users_admins';
    protected $_fields = array( 
        'admin_id' => array( 
            'primary_key' => true, 
            'foreign_table' => 'users'
        ), 
        'admin_fname' => array( 
            'title' => 'Fname', 
            'type'  => 'text' 
        ), 
        'admin_lname' => array( 
            'title' => 'Lname', 
            'type'  => 'text' 
        ), 
    );
    protected $level = '4';
    protected $_prefix_fields = 'admin_';
}


