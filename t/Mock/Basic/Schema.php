<?php
require_once 'Skinny/Schema.php';

class MockBasicSchema extends SkinnySchema
{
    function __construct ( )
    {
        $this->install_table('mock_basic', array(
            'pk'      => 'id',
            'columns' => array('id', 'name', 'delete_fg'),
        ) );
    }
}
