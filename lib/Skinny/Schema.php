<?php  // vim: ts=4 sts=4 sw=4


// SkinnySchema based on DBIx::Skinny 0.04
class SkinnySchema
{
    private $schema_info      = array( );  // -- Hash
    private $installing_table = null;      // -- String
    private $common_triggers  = array( );  // -- Array
    private $inflate_rules    = array( );  // -- Hash
    private $installing_rule  = null;      // -- String
    private $mixins           = array( );  // -- Hash


    function install_table ($table, $install_code)
    {
        $this->installing_table = $table;

        foreach ($install_code as $method => $code) {
            $this->$method($code);
        }

        $this->installing_table = null;
    }


    function pk ($column)
    {
        $this->schema_info[
            $this->installing_table
        ]['pk'] = $column;
    }


    function columns ($columns)
    {
        $this->schema_info[
            $this->installing_table
        ]['columns'] = $columns;
    }


    function trigger ($trigger)
    {
        foreach ($trigger as $trigger_name => $callback) {
            $this->schema_info[
                $this->installing_table
            ]['trigger'][$trigger_name][ ] = $callback;
        }
    }


    function call_trigger ($skinny, $table, $trigger_name, $args)
    {
        $common_triggers = $this->common_triggers[$trigger_name];
        foreach ($common_triggers as $callback) {
            call_user_func($callback, $skinny, $args, $table);
        }

        $triggers = $this->schema_info[$table]['trigger'][$trigger_name];
        foreach ($triggers as $callback) {
            call_user_func($callback, $skinny, $args, $table);
        }
    }


    function install_inflate_rule ($rule, $install_inflate_code)
    {
        $this->installing_rule = $rule;

        foreach ($install_inflate_code as $method => $callback) {
            $this->$method($callback);
        }

        $this->installing_rule = null;
    }


    function inflate ($callback)
    {
        $this->inflate_rules[
            $this->installing_rule
        ]['inflate'] = $callback;
    }


    function deflate ($callback)
    {
        $this->inflate_rules[
            $this->installing_rule
        ]['deflate'] = $callback;
    }


    function call_inflate ($args)
    {
        return $this->do_inflate('inflate', $args);
    }


    function call_deflate ($col, $data)
    {
        return $this->do_inflate('deflate', $col, $data);
    }


    function install_common_trigger ($trigger_name, $callback)
    {
        $this->common_triggers[$trigger_name][ ] = $callback;
    }


    private function do_inflate ($key, $col, $data)
    {
        $inflate_rules = $this->inflate_rules;

        foreach ($inflate_rules as $rule => $inflate) {
            if ( preg_match($rule, $col) && array_key_exists($key, $inflate) ) {
                $data = call_user_func($inflate[$key], $data);
            }
        }

        return $data;
    }


    // provides mixin function
    function __call ($method, $args)
    {
        if ( array_key_exists($method, $this->mixins) ) {
            $func = $this->mixins[$method];
            return call_user_func($func, $args);
        }

        trigger_error('Call to undefinded function: '.$method, E_USER_ERROR);
    }


    function mixin ($include)
    {
        if ( !is_array($include) ) {
            $include = array($include);
        }

        foreach ($include as $obj) {
            if ( !is_object($obj) ) {
                $obj = new $obj;
            }

            $methods = $obj->register_method( );

            foreach ($methods as $method => $callback) {
                $this->mixins[$method] = $callback;
            }
        }
    }
}
