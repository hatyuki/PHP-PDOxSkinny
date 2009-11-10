<?php  // vim: ts=4 sts=4 sw=4


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
