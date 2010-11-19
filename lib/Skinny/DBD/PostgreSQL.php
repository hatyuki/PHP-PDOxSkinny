<?php
require_once 'Skinny/DBD/Base.php';


// SkinnyDriver based on DBIx::Skinny 0.04
class SkinnyDriverPostgreSQL extends SkinnyDriverBase
{
    function dbd_type ( ) { return 'PostgreSQL'; }

    function sql_for_unixtime ( )
    {
        return "TRUNC(EXTRACT('epoch' FROM NOW( )))";
    }

    function last_insert_id ($skinny, $table)
    {
        $schema = $skinny->schema( )->schema_info[$table];
        $seq    = isset($schema['seq'])
                ? $schema['seq']
                : $table.'_'.$schema['pk'].'_seq';

        return $skinny->dbh->lastInsertId($seq);
    }
}
