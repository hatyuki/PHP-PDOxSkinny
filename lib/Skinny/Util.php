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
}
