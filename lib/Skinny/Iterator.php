<?php
require_once 'Skinny/Row.php';


// SkinnyIterator based on DBIx::Skinny 0.04
class SkinnyIterator
{
    public    $opt_table_info = null;      // -- Str
    public    $skinny         = null;      // -- Object
    protected $sth            = null;      // -- Object
    protected $data           = null;      // -- Array
    protected $row_class      = null;      // -- Str
    protected $position       = 0;         // -- Int
    protected $base_row_class = null;      // -- Str
    protected $rows_cache     = array( );  // -- Array
    protected $cache          = true;      // -- Bool


    function __construct ($args)
    {
        foreach ($args as $key => $val) {
            $this->$key = $val;
        }

        $this->reset( );
    }


    protected function iterator ($position)
    {
        if ($position <= 0) {
            return null;
        }

        if ( $this->cache && isset($this->rows_cache[$position]) ) {
            $this->position = $position;
            return $this->rows_cache[$position];
        }

        if ($this->sth) {
            $row =& $this->sth->fetch(PDO::FETCH_ASSOC);

            if ( !$row ) {
                $this->skinny->close_sth($this->sth);
                $this->sth = null;
                return null;
            }
        }
        else if ($this->data && is_array($this->data) ) {
            $row = $this->data[$position];

            if ( !$row ) {
                return null;
            }
        }
        else {
            return null;
        }

        if ( is_a($row, 'SkinnyRow') ) {
            return $row;
        }

        $args = array(
            'row_data'       => $row,
            'skinny'         => $this->skinny,
            'opt_table_info' => $this->opt_table_info,
        );


        if ( class_exists($this->row_class) ) {
            $class = $this->row_class;
            $obj   = new $class($args);
        }
        else {
            $obj = new SkinnyRow($args);
        }

        if ($this->cache) {
            $this->rows_cache[$position] = $obj;
        }

        $this->position = $position;

        return $obj;
    }


    function first ( )
    {
        $this->reset( );

        return $this->next( );
    }


    function next ( )
    {
        return $this->iterator($this->position + 1);
    }


    function current ( )
    {
        return $this->iterator($this->position);
    }

    function back ( )
    {
        return $this->iterator($this->position - 1);
    }


    function all ( )
    {
        $this->reset( );
        $result = array( );

        while ( $row = $this->next( ) ) {
            $result[ ] = $row;
        }

        return $result;
    }


    function all_as_hash ( )
    {
        $this->reset( );
        $result = array( );

        while ( $row = $this->next( ) ) {
            $result[ ] = $row->get_columns( );
        }

        return $result;
    }


    function reset ( )
    {
        $this->position = 0;

        return $this;
    }


    function count ( )
    {
        $rows = $this->reset( )->all( );

        $this->reset( );

        return sizeof($rows);
    }


    function position ( )
    {
        return $this->position;
    }

    function no_cache ( )
    {
        $this->cache = false;
        return $this;
    }


    function with_cache ( )
    {
        $this->cache = true;
        return $this;
    }
}
