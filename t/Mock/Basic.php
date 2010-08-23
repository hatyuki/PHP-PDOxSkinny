<?php
set_include_path('./lib:./t');
require_once 'Skinny.php';
require_once 'Mock/Basic/Schema.php';


class MockBasic extends PDOxSkinny
{
    function __construct ($args=array( ))
    {
        parent::__construct( array_merge(
            array(
                'dsn' => 'sqlite::memory:',
                'raise_error' => true,
            ),
            $args
        ) );
    }


    function setup_test_db ( )
    {
        $this->query("
            CREATE TABLE mock_basic (
                id   integer,
                name text,
                delete_fg int(1) default 0,
                primary key ( id )
            )
        ");
    }
}
