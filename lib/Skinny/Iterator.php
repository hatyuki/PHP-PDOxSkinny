<?php  // vim: ts=4 sts=4 sw=4
require_once 'Skinny/Row.php';


// SkinnyIterator based on DBIx::Skinny 0.04
class SkinnyIterator
{
    private $skinny         = null;      // -- Object
    private $sth            = null;      // -- Object
    private $data           = null;      // -- Array
    private $opt_table_info = null;      // -- Str?
    private $potision       = 0;         // -- Int
    private $rows_cache     = array( );  // -- Array


    function __construct ($args)
    {
        foreach ($args as $key => $val) {
            $this->$key = $val;
        }

        $this->reset( );
    }


    function iterator ( )
    {
        $potision = ++$this->potision;

        if ($row_cache = $this->rows_cache[$potision] ) {
            $this->potision = $potision;
            return $row_cache;
        }

        if ($this->sth) {
            $row = $this->sth->fetch(PDO::FETCH_ASSOC);

            if ( !$row ) {
                $this->skinny->close_sth($this->sth);
                $this->sth = null;
                return ;
            }
        }
        else if ($this->data && is_array($this->data) ) {
            $row = array_shift($this->data);

            if ( !$row ) {
                return ;
            }
        }
        else {
            return ;
        }

        if ( is_a($row, 'SkinnyRow') ) {
            return $row;
        }

        $obj = new SkinnyRow( array(
            'row_data'       => $row,
            'skinny'         => $this->skinny,
            'opt_table_info' => $this->opt_table_info,
        ) );

        $this->rows_cache[$potision] = $obj;
        $this->potition = $potition;

        return $obj;
    }


    function first ( )
    {
        $this->reset( );

        return $this->next( );
    }


    function next ( )
    {
        return $this->iterator( );
    }


    function all ( )
    {
        while ( $row = $this->next( ) ) {
            $result[ ] = $row;
        }

        return $result;
    }


    function reset ( )
    {
        $this->potition = 0;

        return $this;
    }


    function count ( )
    {
        $rows = $this->reset( )->all( );

        $this->reset( );

        return sizeof($rows);
    }
}
