<?php
set_include_path('./lib');
require_once 'Skinny/Schema.php';

class TestSkinnySchema extends SkinnySchema
{
    function __construct ( )
    {
        $this->install_table('mock_basic', array(
            'pk'      => 'id',
            'columns' => array('id', 'name'),
            'trigger' => array(
                'pre_insert' => array($this, 'pre_insert'),
            ),
        ) );

        $this->install_inflate_rule('/.+_at$/', array(
            'inflate' => array($this, 'datetime'),
        ) );
    }
}
