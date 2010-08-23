<?php
set_include_path('./lib:./t');
require_once 'Mock/Basic.php';

class TestSkinnyUpdate extends PHPUnit_Framework_TestCase
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

    function testUpdate ( )
    {
        $this->class->update('mock_basic', array('name' => 'python'), array('id' => 1));
        $row = $this->class->single('mock_basic', array('id' => 1));

        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->name, 'python');
    }

    function testRowObjectUpdate ( )
    {
        $row = $this->class->single('mock_basic', array('id' => 1));
        $this->assertEquals($row->name, 'perl');

        $row->update( array('name' => 'php') );
        $this->assertEquals($row->name, 'php');

        $row = $this->class->single('mock_basic', array('id' => 1));
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->name, 'php');
    }

    function testDataSetUpdate ( )
    {
        $row = $this->class->single('mock_basic', array('id' => 1));
        $this->assertEquals($row->name, 'perl');

        $row->set( array('name' => 'ruby') );
        $this->assertEquals($row->name, 'ruby');

        $row2 = $this->class->single('mock_basic', array('id' => 1));
        $this->assertEquals($row2->name, 'perl');

        $row->update( );
        $row = $this->class->single('mock_basic', array('id' => 1));
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->name, 'ruby');
    }

    function testInjectUpdate ( )
    {
        $row = $this->class->single('mock_basic', array('id' => 1));
        $this->assertEquals($row->name, 'perl');

        $row->update( array('name' => 1) );

        $new_row = $this->class->single('mock_basic', array('id' => 1));
        $this->assertEquals($new_row->name, '1');

        $new_row->update( array('name' => array('inject' => 'name + 1')) );
        $new_row = $this->class->single('mock_basic', array('id' => 1));
        $this->assertEquals($new_row->name, '2');
    }

    function testUpdateRowCount ( )
    {
        $this->class->insert('mock_basic', array(
            'id'   => 2,
            'name' => 'c++',
        ) );
        $cnt = $this->class->update('mock_basic', array('name' => 'java'));
        $this->assertEquals($cnt, 2);
    }
}
