<?php


class SkinnyMixin
{
    protected $skinny = null;  // -- Object

    function __construct ($skinny, $args=null)
    {
        $this->skinny = $skinny;
    }

    function register_method ( )
    {
        return array( );
    }
}
