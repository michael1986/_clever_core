class {$class_name} extends _db_table {
    protected $_table = '{$class_name}';
    protected $_fields = array({*$fields:$field} 
        '{$field[Field]}' => array({?$field[Key] == 'PRI'} 
            'primary_key' => true,{/?}{?$field[Extra] == 'auto_increment'} 
            'auto_increment' => true,{/?}{?$field[sort]} 
            'sort' => true,{/?}{?$field[created]} 
            'created' => true,{/?}{?$field[foreign_table]} 
            'foreign_table' => '{$field[foreign_table]}',{/?}{?$field[modified]} 
            'modified' => true,{/?}{?$field[Key] != 'PRI' && !$field[sort] && !$field[created] && !$field[modified]} 
            'title' => '{$field[title]}', 
            'type'  => 'text', 
            'adjust_output' => '_simplest_filter'{/?} 
        ),{/*} 
    );
    protected $_prefix_fields = '{$prefix_fields}';
}


