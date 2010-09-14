<?php
restore_include_path( );
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Basic.php';


class TestSkinnyFindOrCreate extends PHPUnit_Framework_TestCase
{
    private $class;

    function setUp ( )
    {
        $this->class = new MockBasic( );
        $this->class->setup_test_db( );
    }

    function testFindOrCreate ( )
    {
        $mock_basic = $this->class->find_or_create('mock_basic', array(
            'id'   => 1,
            'name' => 'perl',
        ) );
        $this->assertEquals($mock_basic->name, 'perl');
        $this->assertEquals($mock_basic->delete_fg, 0);

        $mock_basic = $this->class->find_or_create('mock_basic', array(
            'id'   => 1,
            'name' => 'perl',
        ) );
        $this->assertEquals($mock_basic->name, 'perl');
        $this->assertEquals($mock_basic->delete_fg, 0);

        $count = $this->class->count('mock_basic', 'id', array('name' => 'perl'));
        $this->assertEquals($count, 1);
    }

    function testFindOrInsert ( )
    {
        $mock_basic = $this->class->find_or_insert('mock_basic', array(
            'id'   => 2,
            'name' => 'ruby',
        ) );
        $this->assertEquals($mock_basic->name, 'ruby');
        $this->assertEquals($mock_basic->delete_fg, 0);

        $mock_basic = $this->class->find_or_insert('mock_basic', array(
            'id'   => 2,
            'name' => 'ruby',
        ) );
        $this->assertEquals($mock_basic->name, 'ruby');
        $this->assertEquals($mock_basic->delete_fg, 0);

        $count = $this->class->count('mock_basic', 'id', array('name' => 'ruby'));
        $this->assertEquals($count, 1);
    }
}
