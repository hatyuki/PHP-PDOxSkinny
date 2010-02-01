<?php


// SkinnyTransaction based on DBIx::Skinny 0.04
class SkinnyTransaction
{
    protected $status = false;  // -- Bool
    protected $skinny = null;   // -- Object


    function __construct ($skinny)
    {
        $this->status =  false;
        $this->skinny = $skinny;

        $skinny->txn_begin( );
    }


    function __get ($name)
    {
        switch ($name) {
        case 'rollback': break;
        case 'commit':   break;

        default:
            trigger_error("call unknown method: $name", E_USER_ERROR);
        }

        return $this->$name( );
    }


    function rollback ( )
    {
        if ($this->status) {
            return false;
        }

        $this->skinny->txn_rollback( );
        $this->status = true;

        return true;
    }


    function commit ( )
    {
        if ($this->status) {
            return false;
        }

        $this->skinny->txn_commit( );
        $this->status = true;

        return true;
    }


    function __destruct ( )
    {
        try {
            $this->skinny->txn_rollback( );
        }
        catch (Exception $e) {
            trigger_error('Rollback failed: '.$e->getMessage( ), E_USER_ERROR);
        }
    }
}
