<?php


class SkinnyMixin
{
    protected $skinny = null;  // -- Object

    function __construct ($skinny)
    {
        $this->skinny = $skinny;
    }

    function register_method ( )
    {
        return array( );
    }
}
