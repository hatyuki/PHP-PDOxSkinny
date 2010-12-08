<?php


// SkinnyRow based on DBIx::Skinny 0.04
class SkinnyRow
{
    public    $opt_table_info    = null;      // -- Str
    public    $skinny            = null;      // -- Object
    protected $select_columns    = array( );  // -- Array
    protected $select_alias      = array( );  // -- Array
    protected $row_data          = array( );  // -- Hash
    protected $get_column_cached = array( );  // -- Hash
    protected $dirty_columns     = array( );  // -- Hash


    function __construct ($args)
    {
        $this->select_columns    = array_keys($args['row_data']);
        $this->row_data          = $args['row_data'];
        $this->opt_table_info    = $args['opt_table_info'];
        $this->skinny            = $args['skinny'];
        $this->dirty_columns     = array( );

        foreach ($this->select_columns as $alias) {
            $col = strtolower( preg_replace('/.+\.(.+)/', "$1", $alias) );
            $this->select_alias[ ] = $col;
        }
    }


    function __call ($name, $null)
    {
        return $this->$name;
    }


    function __get ($name)
    {
        if ( !in_array($name, $this->select_alias) ) {
            $trace = debug_backtrace( );
            $trace = $trace[0];
            $trace = 'at '.$trace['file'].' line '.$trace['line'];
            trigger_error(
                "$trace\nunknown column: $name", E_USER_ERROR
            );
        }

        if ( !array_key_exists($name, $this->get_column_cached) ) {
            $data = $this->row_data[$name];
            $this->get_column_cached[$name] = $this->skinny->schema->call_inflate($name, $data);
        }

        $col = $this->get_column_cached[$name];

        return $col;
    }


    function __set ($name, $val)
    {
        return $this->set( array($name => $val) );
    }


    function get_column ($col)
    {
        return $this->$col;
    }

    function get_raw_column ($col)
    {
        return $this->row_data[$col];
    }

    function get_columns ( )
    {
        $data = array( );

        foreach ($this->select_columns as $col) {
            $data[$col] = $this->get_column($col);
        }

        return $data;
    }

    function get_raw_columns ( )
    {
        $data = array( );

        foreach ($this->select_columns as $col) {
            $data[$col] = $this->get_raw_column($col);
        }

        return $data;
    }

    function set ($args)
    {
        foreach ($args as $col => $val) {
            $this->row_data[$col] =
                $this->skinny->schema( )->call_deflate($col, $val);
            $this->get_column_cached[$col] = $val;
            $this->dirty_columns[$col]  = true;
            $this->select_columns[$col] = $val;
            $this->select_alias[ ]      = $col;
        }

        return $this;
    }


    function get_dirty_columns ( )
    {
        $rows = array( );

        foreach ($this->dirty_columns as $col => $dirty) {
            if ($dirty) {
                $rows[$col] = $this->get_column($col);
            }
        }

        return $rows;
    }


    function insert ($args)
    {
        return $this->skinny->find_or_create(
            $this->opt_table_info, $this->get_column( )
        );
    }


    function update ($args=array( ), $table=null)
    {
        $table = $table ? $table : $this->opt_table_info;
        $args  = $args  ? $args  : $this->get_dirty_columns( );

        if ( is_array($table) ) {
            $table = array_shift($table);
        }

        $where  = $this->update_or_delete_cond($table);
        $result = call_user_func_array(
            array($this->skinny, 'update'),
            array($table, &$args, $where)
        );

        $this->set($args);

        return $result;
    }


    function delete ($table=null)
    {
        $table = $table ? $table : $this->opt_table_info;

        $where = $this->update_or_delete_cond($table);

        return $this->skinny->delete($table, $where);
    }


    function select_columns ( ) { return $this->select_columns; }


    protected function update_or_delete_cond ($table)
    {
        if ( empty($table) ) {
            trigger_error('no table info', E_USER_ERROR);
        }

        if ( is_array($table) ) {
            $table = array_shift($table);
        }

        $schema_info =& $this->skinny->schema->schema_info;

        if ( !isset($schema_info[$table]) ) {
            trigger_error("unknown table: $table", E_USER_ERROR);
        }

        $pk = $schema_info[$table]['pk'];

        if ( !$pk ) {
            trigger_error("$table have no pk", E_USER_ERROR);
        }

        if ( !in_array($pk, $this->select_columns) ) {
            trigger_error("can't get primary column in your query", E_USER_ERROR);
        }

        return array($pk => $this->$pk( ));
    }
}
