<?php


// SkinnyTransaction based on DBIx::Skinny 0.04
class SkinnyTransaction
{
    protected $status = false;  // -- Bool
    protected $skinny = null;   // -- Object
    protected $name   = null;   // -- String


    function __construct ($skinny, $name='')
    {
        $this->status =  false;
        $this->skinny = $skinny;
        $this->name   = $name;

        $skinny->txn_begin($name);
    }


    function __get ($name)
    {
        switch ($name) {
        case 'rollback':
        case 'commit':
            return $this->$name( );

        default:
            trigger_error("call unknown method: $name", E_USER_ERROR);
        }
    }


    function rollback ( )
    {
        if ($this->status) {
            return false;
        }

        $this->skinny->txn_rollback($this->name);
        $this->status = true;

        return true;
    }


    function commit ( )
    {
        if ($this->status) {
            return false;
        }

        $this->skinny->txn_commit($this->name);
        $this->status = true;

        return true;
    }


    function __destruct ( )
    {
        if ($this->status) {
            return null;
        }

        try {
            $this->skinny->txn_rollback($this->name);
        }
        catch (Exception $e) {
            trigger_error('Rollback failed: '.$e->getMessage( ), E_USER_ERROR);
        }
    }
}
