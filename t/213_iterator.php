<?php
restore_include_path( );
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Basic.php';

class TestSkinnyIterator extends PHPUnit_Framework_TestCase
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
        $this->class->insert('mock_basic', array(
            'id'   => 2,
            'name' => 'ruby',
        ) );
        $this->class->insert('mock_basic', array(
            'id'   => 3,
            'name' => 'php',
        ) );
    }

    function tearDown ( )
    {
        $this->class = null;
    }

    function testIteratorWithCache ( )
    {
        $itr = $this->class->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $this->assertEquals($itr->position( ), 0);
        $this->assertEquals($itr->count( ), 3);

        $rows = $itr->all( );
        $this->assertEquals(sizeof($rows), 3);
        $this->assertEquals($itr->position( ), 3);

        $itr->reset( );
        $this->assertEquals($itr->position( ), 0);

        $row1  = $itr->next( );
        $this->assertTrue( is_a($row1, 'SkinnyRow') );
        $this->assertEquals($itr->position( ), 1);
        $this->assertEquals($row1->id, 1);
        $this->assertEquals($row1->name, 'perl');

        $row2 = $itr->next( );
        $this->assertTrue( is_a($row2, 'SkinnyRow') );
        $this->assertEquals($itr->position( ), 2);
        $this->assertEquals($row2->id, 2);
        $this->assertEquals($row2->name, 'ruby');

        $row3 = $itr->next( );
        $this->assertTrue( is_a($row3, 'SkinnyRow') );
        $this->assertEquals($itr->position( ), 3);
        $this->assertEquals($row3->id, 3);
        $this->assertEquals($row3->name, 'php');

        $this->assertNull($itr->next( ));
        $this->assertEquals($itr->position( ), 3);

        $itr->reset( );
        $row1 = $itr->first( );
        $this->assertTrue( is_a($row1, 'SkinnyRow') );
    }


    function testIteratorNoCacheAll ( )
    {
        $itr = $this->class->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $itr->cache = false;

        $this->assertEquals($itr->count( ), 3);
        $rows = $itr->all( );
        $this->assertEquals(sizeof($rows), 0);

        $this->assertEquals($itr->reset( ), $itr);
        $this->assertNull($itr->next( ));
    }


    function testIteratorNoCache ( )
    {
        $itr = $this->class->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $this->assertEquals($itr->position( ), 0);

        $itr->cache = false;

        $row1 = $itr->next( );
        $this->assertTrue( is_a($row1, 'SkinnyRow') );
        $this->assertEquals($itr->position( ), 1);

        $row2 = $itr->next( );
        $this->assertTrue( is_a($row2, 'SkinnyRow') );
        $this->assertEquals($itr->position( ), 2);

        $row3 = $itr->next( );
        $this->assertTrue( is_a($row3, 'SkinnyRow') );
        $this->assertEquals($itr->position( ), 3);

        $this->assertNull( $itr->next( ) );
        $this->assertEquals($itr->position( ), 3);
        $this->assertEquals($itr->reset( ), $itr);
        $this->assertEquals($itr->position( ), 0);
        $this->assertNull($itr->first( ));
    }


    function testIteratorNoMakeObject ( )
    {
        $itr = $this->class->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $itr->suppress_objects = true;

        $row = $itr->next( );
        $this->assertTrue( is_array($row) );
        $this->assertEquals($row, array(
            'id'        => 1,
            'delete_fg' => 0,
            'name'      => 'perl',
        ) );

        $itr->suppress_objects = false;
        $row = $itr->next( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $dat = $row->get_columns( );
        $this->assertEquals($dat, array(
            'id'        => 2,
            'delete_fg' => 0,
            'name'      => 'ruby',
        ) );
    }


    function testIteratorWithSuppressRowObject ( )
    {
        $this->class->suppress_row_objects = true;
        $itr = $this->class->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $row = $itr->next( );
        $this->assertTrue( is_array($row) );
        $this->assertEquals($row, array(
            'id'        => 1,
            'delete_fg' => 0,
            'name'      => 'perl',
        ) );

        $row = $itr->next( );
        $this->assertTrue( is_array($row) );
        $this->assertEquals($row, array(
            'id'        => 2,
            'delete_fg' => 0,
            'name'      => 'ruby',
        ) );

        $itr->reset( );

        $row = $itr->next( );
        $this->assertTrue( is_array($row) );
        $this->assertEquals($row, array(
            'id'        => 1,
            'delete_fg' => 0,
            'name'      => 'perl',
        ) );
    }


    function testIteratorWithSuppressRowObjectOnWithCache ( )
    {
        $this->class->suppress_row_objects = true;
        $itr = $this->class->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $row = $itr->next( );
        $this->assertTrue( is_array($row) );
    }


    function testBack ( )
    {
        $itr = $this->class->search('mock_basic');

        $this->assertNull($itr->back( ));

        $row = $itr->next( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');

        $row = $itr->next( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 2);
        $this->assertEquals($row->name, 'ruby');

        $row = $itr->back( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');

        $row = $itr->next( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 2);
        $this->assertEquals($row->name, 'ruby');

        $row = $itr->next( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 3);
        $this->assertEquals($row->name, 'php');

        $this->assertNull($itr->next( ));
    }

    function testCurrent ( )
    {
        $itr = $this->class->search('mock_basic');

        $row1 = $itr->next( );
        $row2 = $itr->current( );
        $this->assertTrue( is_a($row1, 'SkinnyRow') );
        $this->assertTrue( is_a($row2, 'SkinnyRow') );
        $this->assertEquals($row1->id, 1);
        $this->assertEquals($row2->name, 'perl');
        $this->assertEquals($row1, $row2);

        $this->assertEquals($itr->next( ), $itr->current( ));
        $this->assertEquals($itr->next( ), $itr->current( ));
        $this->assertEquals($itr->back( ), $itr->current( ));
        $this->assertEquals($itr->next( ), $itr->current( ));
    }
}
