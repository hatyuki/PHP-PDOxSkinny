<?php
require_once 'Mock/Basic.php';


class MockTrigger extends MockBasic
{
    function setup_test_db ( )
    {
        $this->query("
            CREATE TABLE mock_trigger_pre (
                id   integer,
                name text
            )
        ");

        $this->query("
            CREATE TABLE mock_trigger_post (
                id   integer,
                name text
            )
        ");

        $this->query("
            CREATE TABLE mock_trigger_post_delete (
                id   integer,
                name text
            )
        ");
    }
}


class MockTriggerSchema extends SkinnySchema
{
    function register_schema ( )
    {
        $this->install_table('mock_trigger_pre', array(
            'pk'      => 'id',
            'columns' => array('id', 'name'),
            'trigger' => array(
                'pre_insert' => array(
                    array($this, 'pre_insert'),
                    array($this, 'pre_insert_s'),
                ),
                'post_insert' => array(
                    array($this, 'post_insert'),
                ),
                'pre_update' => array(
                    array($this, 'pre_update'),
                ),
                'post_update' => array(
                    array($this, 'post_update'),
                ),
                'pre_delete' => array(
                    array($this, 'pre_delete'),
                ),
                'post_delete' => array(
                    array($this, 'post_delete'),
                ),
            ),
        ) );

        $this->install_table('mock_trigger_post', array(
            'pk'      => 'id',
            'columns' => array('id', 'name'),
        ) );

        $this->install_table('mock_trigger_post_delete', array(
            'pk'      => 'id',
            'columns' => array('id', 'name'),
        ) );
    }

    function pre_insert ($skinny, $args)
    {
        $args['name'] = 'pre_insert';
    }

    function pre_insert_s ($skinny, $args)
    {
        $args['name'] .= '_s';
    }

    function post_insert ($skinny, $args)
    {
        $skinny->insert('mock_trigger_post', array(
            'id'   => 1,
            'name' => 'post_insert',
        ) );
    }

    function pre_update ($skinny, $args)
    {
        $args['name'] = 'pre_update';
    }

    function post_update ($skinny, $args)
    {
        $skinny->update(
            'mock_trigger_post',
            array('name' => 'post_update'),
            array('id'   => 1)
        );
    }

    function pre_delete ($skinny, $args)
    {
        $skinny->delete('mock_trigger_post', array(
            'id' => 1
        ) );
    }

    function post_delete ($skinny, $args)
    {
        $skinny->insert('mock_trigger_post_delete', array(
            'id'   => 1,
            'name' => 'post_delete',
        ) );
    }
}
