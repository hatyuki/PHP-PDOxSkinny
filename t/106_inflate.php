<?php
restore_include_path( );
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Inflate.php';


class TestSkinnyInflate extends PHPUnit_Framework_TestCase
{
    protected $class;

    function setUp ( )
    {
        $this->class = new MockInflate( );
        $this->class->setup_test_db( );

        $name = new MockInflateName( array('name' => 'perl') );
        $row  = $this->class->insert('mock_inflate', array(
            'id'   => 1,
            'name' => $name,
        ) );
    }

    function tearDown ( )
    {
        $this->class = null;
    }


    function testInflateInsertData ( )
    {
        $name = new MockInflateName( array('name' => 'python') );
        $row  = $this->class->insert('mock_inflate', array(
            'id'   => 2,
            'name' => $name,
        ) );

        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertTrue( is_a($row->name, 'MockInflateName') );
        $this->assertEquals($row->name->name, 'python');
    }


    function testInflateUpdateData ( )
    {
        $name = new MockInflateName( array('name' => 'ruby') );
        $this->assertEquals(
            $this->class->update(
                'mock_inflate',
                array('name' => $name),
                array('id'   => 1)
            ),
            1
        );
        $row = $this->class->single('mock_inflate', array('id' => 1));

        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertTrue( is_a($row->name, 'MockInflateName') );
        $this->assertEquals($row->name->name, 'ruby');
    }


    function testInflateUpdateRow ( )
    {
        $row  = $this->class->single('mock_inflate', array('id' => 1));
        $name = $row->name;
        $name->name = 'perl';
        $row->update( array('name' => $name) );

        $updated = $this->class->single('mock_inflate', array('id' => 1));
        $this->assertTrue( is_a($updated->name, 'MockInflateName') );
        $this->assertEquals($updated->name->name, 'perl');
    }
}
