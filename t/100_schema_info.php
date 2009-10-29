<?php  // vim: ts=4 sts=4 sw=4
require_once 'PHPUnit/Framework.php';

set_include_path('./t');
require_once 'Mock/Basic/Schema.php';

class TestSkinnySQL extends PHPUnit_Framework_TestCase
{
    private $class;

    function testSchema ( )
    {
        $schema = new TestSkinnySchema( );

        var_dump($schema);
    }
}
