<?php  // vim: ts=4 sts=4 sw=4


// SkinnyTransaction based on DBIx::Skinny 0.04
class SkinnyTransaction
{
    private $status = false;
    private $skinny = null;


    function __construct ($skinny)
    {
        $this->status = false;
        $this->skinny = $skinny;

        $skinny->txn_begin( );
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
