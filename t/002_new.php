<?php
restore_include_path( );
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Basic.php';


class TestSkinnyNew extends PHPUnit_Framework_TestCase
{
    private $class;

    function setUp ( )
    {
        $this->class = new MockBasic( );
        $this->class->reconnect( array(
            'dsn'      => 'sqlite:./t/main.db',
            'username' => '',
            'password' => '',
        ) );
        $this->class->setup_test_db( );
        $this->class->insert('mock_basic', array(
            'id'   => 1,
            'name' => 'perl',
        ) );
        $this->class->insert('mock_basic', array(
            'id'   => 2,
            'name' => 'python',
        ) );
    }

    function tearDown ( )
    {
        $files = array('other', 'main');

        foreach ($files as $name) {
            $file = "./t/$name.db";

            if ( file_exists($file) ) {
                unlink($file);
            }
        }
    }

    function testSearch ( )
    {
        $itr = $this->class->search('mock_basic', array('id' => 1));
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );

        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
    }

    function testDoNewOtherConnection ( )
    {
        $model = new MockBasic( array(
            'dsn'      => 'sqlite:./t/other.db',
            'username' => '',
            'password' => '',
        ) );

        $model->setup_test_db( );
        $model->insert('mock_basic', array(
            'id'   => 1,
            'name' => 'perl',
        ) );

        $itr = $model->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );

        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');

        $this->assertEquals($this->class->count('mock_basic', 'id'), 2);
        $this->assertEquals($model->count('mock_basic', 'id'), 1);
    }

    // SKIP: 未実装
    function testDoNew ( )
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

        $model = new MockBasic( );

        $itr = $model->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );

        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');

        $this->assertEquals($this->class->count('mock_basic', 'id'), 2);
        $this->assertEquals($model->count('mock_basic', 'id'), 1);
    }

    // SKIP: 未実装
    function testDoNewWithPDO ( )
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

        $pdo   = new PDO('sqlite::memory:', '', '');
        $model = new MockBasic( array('pdo' => $pdo) );

        $model->setup_test_db( );
        $model->insert('mock_basic', array(
            'id'   => 1,
            'name' => 'perl',
        ) );

        $itr = $model->search('mock_basic');
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );

        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );

        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');

        $this->assertEquals($this->class->count('mock_basic', 'id'), 2);
        $this->assertEquals($model->count('mock_basic', 'id'), 1);
    }
}
