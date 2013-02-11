<?php
class back_favorites extends _db_table {
    protected $_table = 'back_favorites';
    protected $_fields = array( 
        'f_id' => array( 
            'primary_key' => true, 
            'auto_increment' => true, 
        ), 
        'f_title' => array( 
            'title' => 'Title', 
            'type'  => 'text', 
            'adjust_output' => '_simplest_filter' 
        ), 
        'f_link' => array( 
            'title' => 'Link', 
            'type'  => 'text', 
            'adjust_output' => '_simplest_filter' 
        ), 
        'f_sort' => array( 
            'sort' => true, 
        ), 
    );
    protected $_prefix_fields = 'f_';
}


