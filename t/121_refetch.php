<?php
restore_include_path( );
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Basic.php';

class TestSkinnyRefetch extends PHPUnit_Framework_TestCase
{
    private $class;

    function setUp ( )
    {
        $this->class = new MockBasic( );
        $this->class->setup_test_db( );
    }

    function tearDown ( )
    {
        $this->class = null;
    }

    function testRefetch ( )
    {
        $row = $this->class->insert('mock_basic', array(
            'id' => 1,
            'name' => 'perl',
        ) );

        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->name, 'perl');

        $refetch = $row->refetch( );
        $this->assertTrue( is_a($refetch, 'SkinnyRow') );
        $this->assertEquals($refetch->name, 'perl');
    }
}
