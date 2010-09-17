<?php
require_once 'Mock/Basic.php';


class MockInflate extends MockBasic
{
    function setup_test_db ( )
    {
        $this->query("
            CREATE TABLE mock_inflate (
                id   integer,
                name text
            )
        ");
    }
}


class MockInflateSchema extends SkinnySchema
{
    function register_schema ( )
    {
        $this->install_table('mock_inflate', array(
            'pk'      => 'id',
            'columns' => array('id', 'name'),
        ) );

        $this->install_inflate_rule('^name$', array(
            'inflate' => array($this, 'inflate_name'),
            'deflate' => array($this, 'deflate_name'),
        ) );
    }


    function inflate_name ($value)
    {
        return new MockInflateName( array('name' => $value) );
    }


    function deflate_name ($value)
    {
        return $value->name;
    }
}


class MockInflateName
{
    public $name;

    function __construct ($args)
    {
        $this->name = $args['name'];
    }
}
