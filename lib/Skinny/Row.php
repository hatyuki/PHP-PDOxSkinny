<?php  // vim: ts=4 sts=4 sw=4


// SkinnyRow based on DBIx::Skinny 0.04
class SkinnyRow
{
    private   $select_columns    = null;      // -- Array
    private   $row_data          = array( );  // -- Hash
    private   $get_column_cached = array( );  // -- Hash
    private   $dirty_columns     = false;     // -- Bool
    private   $opt_table_info    = null;      // -- Str
    protected $skinny            = null;      // -- Object


    function __construct ($args)
    {
        $this->select_columns    = array_keys($args['row_data']);
        $this->row_data          = $args['row_data'];
        $this->opt_table_info    = $args['opt_table_info'];
        $this->skinny            = $args['skinny'];
        $this->dirty_columns     = array( );

        foreach ($this->select_columns as $alias) {
            $col = strtolower( preg_replace('/.+\.(.+)/', "$1", $alias) );

            if ( !$this->get_column_cached[$col] ) {
                $data = $this->get_column($col);
                $this->get_column_cached[$col] =
                    $this->skinny->schema( )->call_inflate($col, $data);
            }
        }
    }


    function __call ($name, $args)
    {
        $col = $this->get_column_cached[$name];

        if ( !in_array($name, $this->select_columns) ) {
            trigger_error("unknown column: $name", E_USER_ERROR);
        }

        return $col;
    }


    function get_column ($col)
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


    function set ($args)
    {
        foreach ($args as $col => $val) {
            $this->row_data[$col] =
                $this->skinny->schema( )->call_deflate($col, $val);
            $this->get_column_cached[$col] = $val;
            $this->dirty_columns[$col] = true;
        }

        return $this;
    }


    function get_dirty_columns ( )
    {
        $rows = array( );

        foreach ($this->dirty_columns as $col => $dirty) {
            $rows[$col] = $this->get_column($col);
        }

        return $rows;
    }


    function insert ($args, $table)
    {
        return $this->skinny->find_or_create(
            $this->opt_table_info, $this->get_column( )
        );
    }


    function update ($args, $table)
    {
        $table = $table ? $table : $this->opt_table_info;
        $args  = $args  ? $args  : $this->get_dirty_columns( );

        $where  = $this->update_or_delete_cond($table);
        $result = $this->skinny->update($table, $args, $where);

        $this->set($args);

        return $result;
    }


    function delete ($table=null)
    {
        $table = $table ? $table : $this->opt_table_info;

        $where = $this->update_or_delete_cond($table);

        return $this->skinny->delete($table, $where);
    }


    private function update_or_delete_cond ($table)
    {
        if ( !$table ) {
            trigger_error('no table info', E_USER_ERROR);
        }

        $schema_info = $this->skinny->schema( )->schema_info;

        if ( !$schema_info[$table] ) {
            trigger_error("unknown table: $table", E_USER_ERROR);
        }

        $pk = $schema_info[$table]['pk'];

        if ( !$pk ) {
            trigger_error("$table have no pk", E_USER_ERROR);
        }

        if ( array_search($pk, $this->select_columns) === false) {
            trigger_error("can't get primary column in your query", E_USER_ERROR);
        }

        return array($pk => $this->$pk( ));
    }
}
