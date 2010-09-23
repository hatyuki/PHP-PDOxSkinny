<?php
restore_include_path( );
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Basic.php';


class TestSkinnySchemaInfo extends PHPUnit_Framework_TestCase
{
    function testSchemaInfo ( )
    {
        $skinny = new MockBasic( );
        $skinny->setup_test_db( );

        $info = $skinny->schema->schema_info;

        $this->assertEquals($info, array(
            'mock_basic' => array(
                'pk'      => 'id',
                'columns' => array('id', 'name', 'delete_fg'),
            ),
        ) );
    }
}
