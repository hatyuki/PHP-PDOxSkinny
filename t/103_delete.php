<?php
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Basic.php';

class TestSkinnyDelete extends PHPUnit_Framework_TestCase
{
    private $class;

    function setUp ( )
    {
        $this->class = new MockBasic( );
        $this->class->setup_test_db( );
        $this->class->insert('mock_basic', array(
            'id'   => 1,
            'name' => 'perl',
        ) );
    }

    function testDelete ( )
    {
        $count = $this->class->count('mock_basic', 'id');
        $this->assertEquals($count, 1);

        $this->class->delete('mock_basic', array('id' => 1));
        $count = $this->class->count('mock_basic', 'id');
        $this->assertEquals($count, 0);
    }

    function testDeleteRowCount ( )
    {
        $this->class->insert('mock_basic', array(
            'id'   => 2,
            'name' => 'perl',
        ) );

        $count = $this->class->delete('mock_basic', array('name' => 'perl'));
        $this->assertEquals($count, 2);

        $count = $this->class->count('mock_basic', 'id');
        $this->assertEquals($count, 0);
    }

    function testDeleteRowObject ( )
    {
        $count = $this->class->count('mock_basic', 'id');
        $this->assertEquals($count, 1);

        $row = $this->class->single('mock_basic', array('id' => 1));
        $row->delete( );

        $count = $this->class->count('mock_basic', 'id');
        $this->assertEquals($count, 0);
    }
}
