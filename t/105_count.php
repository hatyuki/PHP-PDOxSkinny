<?php
set_include_path('./lib:./t');
require_once 'Mock/Basic.php';


class TestSkinnyCount extends PHPUnit_Framework_TestCase
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

    function testCount ( )
    {
        $count = $this->class->count('mock_basic', 'id');
        $this->assertEquals($count, 1);

        $this->class->insert('mock_basic', array(
            'id'   => 2,
            'name' => 'ruby',
        ) );
        $count = $this->class->count('mock_basic', 'id');
        $this->assertEquals($count, 2);

        $count = $this->class->count('mock_basic', 'id', array('name' => 'perl'));
        $this->assertEquals($count, 1);
    }

    function testIteratorCount ( )
    {
        $this->class->insert('mock_basic', array(
            'id'   => 2,
            'name' => 'ruby',
        ) );
        $count = $this->class->search('mock_basic', array( ))->count( );
        $this->assertEquals($count, 2);
    }
}
