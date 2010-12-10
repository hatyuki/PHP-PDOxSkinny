<?php


class SkinnyUtil
{
    static function ref ($value)
    {
        if ( is_array($value) ) {
            $keys = implode( array_keys($value) );

            return self::is_int($keys) ? 'ARRAY' : 'HASH';
        }
        else if ( is_object($value) ) {
            return get_class($value);
        }

        return 'SCALAR';
    }


    static function is_int ($value)
    {
        return preg_match('/^[-]?\d+$/', $value) == 1;
    }


    static function camelize ($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }

    static function deprecated ($alt)
    {
        $trace  = debug_backtrace( );
        $method = $trace[1]['function'];
        trigger_error("'$method' is deprecated. use '$alt' instead.", E_USER_NOTICE);
    }
}
