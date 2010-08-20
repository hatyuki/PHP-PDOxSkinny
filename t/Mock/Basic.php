<?php
set_include_path('./lib');
require_once 'Skinny.php';

set_include_path('./t');
require_once 'Mock/Basic/Schema.php';


class MockBasic extends PDOxSkinny
{
    function __construct ( )
    {
        parent::__construct( array(
            'dsn' => 'sqlite::memory:',
            'raise_error' => true,
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