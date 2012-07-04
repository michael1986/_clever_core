<?php
/**
* @author Dmitry Bazavluk <dmitry.bazavluk@gmail.com>
* @version 1.0
* @package Users
*/

class user_access_levels extends _db_table {
    protected $_fields = array( 
        'al_id' => array(
            'primary_key' => true,
            'auto_increment' => true,
        ),  
        'al_title',  
        'al_level',  
        'al_table',  
        'al_sort',  
    );
}


