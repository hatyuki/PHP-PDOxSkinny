<?php

class MockBasicMixin
{
    function register_method ( )
    {
        return array(
            'bourbon' => array($this, 'bourbon'),
            'house'   => array($this, 'house'),
            'foo'     => array($this, 'baz'),
        );
    }


    function bourbon ( )
    {
        return 'Bourbon';
    }


    function house ( )
    {
        return 'House';
    }

    function baz($args)
    {
        return 'Bourbon'.$args[0].'House';
    }
}
