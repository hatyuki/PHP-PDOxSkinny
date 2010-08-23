<?php
require_once 'PHPUnit/Framework.php';

set_include_path('./t');
require_once 'Mock/Basic.php';

class TestSkinnyInsert extends PHPUnit_Framework_TestCase
{
    private $class;

    function setUp ( )
    {
        $this->class = new MockBasic( );
        $this->class->setup_test_db( );
    }

    function testInsert ( )
    {
        $row = $this->class->insert('mock_basic', array(
            'id'   => 1,
            'name' => 'perl',
        ) );

        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
    }

    function testCreate ( )
    {
        $row = $this->class->create('mock_basic', array(
            'id'   => 2,
            'name' => 'ruby',
        ) );

        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 2);
        $this->assertEquals($row->name, 'ruby');
    }
}
