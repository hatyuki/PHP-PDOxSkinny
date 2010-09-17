<?php
restore_include_path( );
set_include_path(get_include_path( ).':./lib:./t');


class TestSkinnyCompile extends PHPUnit_Framework_TestCase
{
    private $class;

    function testCompile ( )
    {
        try {
            require_once 'Skinny.php';
            require_once 'Skinny/Schema.php';
            require_once 'Skinny/SQL.php';
            require_once 'Skinny/Row.php';
            require_once 'Skinny/Mixin.php';
        }
        catch (Exception $e) {
            $this->fail( );
        }
    }
}
