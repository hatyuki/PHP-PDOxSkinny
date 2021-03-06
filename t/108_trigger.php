<?php
restore_include_path( );
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Trigger.php';


class TestSkinnyTrigger extends PHPUnit_Framework_TestCase
{
    protected $class;

    function setUp ( )
    {
        $this->class = new MockTrigger( );
        $this->class->setup_test_db( );
    }

    function tearDown ( )
    {
        $this->class = null;
    }


    function testSchemaInfo ( )
    {
        $this->assertTrue( is_a($this->class->schema, 'MockTriggerSchema') );

        $info = $this->class->schema->schema_info;
        $keys = array(
            'mock_trigger_pre', 'mock_trigger_post', 'mock_trigger_post_delete',
        );

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $info);
        }

        $trigger = $info['mock_trigger_pre']['trigger'];
        $keys = array(
            'pre_insert', 'post_insert',
            'pre_update', 'post_update',
            'pre_delete', 'post_delete',
        );

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $trigger);
        }

        $this->assertEquals(sizeof($trigger['pre_insert']), 2);
        $this->assertEquals($trigger['pre_insert'][0][1], 'pre_insert');
        $this->assertEquals($trigger['pre_insert'][1][1], 'pre_insert_s');
    }


    function testInsertTrigger ( )
    {
        $row = $this->class->insert('mock_trigger_pre', array(
            'id' => 1,
        ) );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->name, 'pre_insert_s');

        $p_row = $this->class->single('mock_trigger_post', array(
            'id' => 1,
        ) );
        $this->assertTrue( is_a($p_row, 'SkinnyRow') );
        $this->assertEquals($p_row->name, 'post_insert');

    }

    function testUpdateTrigger ( )
    {
        $this->class->insert('mock_trigger_pre', array(
            'id' => 1,
        ) );
        $res = $this->class->update('mock_trigger_pre', array( ));

        $p_row = $this->class->single('mock_trigger_post', array(
            'id' => 1,
        ) );
        $this->assertTrue( is_a($p_row, 'SkinnyRow') );
        $this->assertEquals($p_row->name, 'post_update');
    }

    function testUpdateAffectRowObject ( )
    {
        $row = $this->class->insert('mock_trigger_pre', array(
            'id'   => 2,
            'name' => 'pre',
        ) );

        $this->assertEquals($row->update( array('id' => 2) ), 1);
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->name, 'pre_update');
    }


    function testDeleteTrigger ( )
    {
        $this->class->insert('mock_trigger_pre', array(
            'id' => 1,
        ) );
        $this->class->insert('mock_trigger_pre', array(
            'id'   => 2,
            'name' => 'pre',
        ) );
        $this->class->delete('mock_trigger_pre', array( ));

        $count = $this->class->count('mock_trigger_post', 'id', array( ));
        $this->assertEquals($count, 0);

        $row = $this->class->single('mock_trigger_post_delete', array('id' => 1));
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->name, 'post_delete');
    }
}
