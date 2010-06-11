<?php
require_once 'Skinny/SQL.php';


// SkinnyDriver based on DBIx::Skinny 0.04
class SkinnyDriverPostgreSQL
{
    function dbd_type            ( ) { return 'PostgreSQL'; }
    function query_builder_class ( ) { return 'SkinnySQL'; }
    function quote               ( ) { return '"'; }
    function name_sep            ( ) { return '.'; }


    function last_insert_id ($skinny, $table)
    {
        $schema = $skinny->schema( )->schema_info[$table];
        $seq    = isset($schema['seq'])
                ? $schema['seq']
                : $table.'_'.$schema['pk'].'_seq';

        return $skinny->dbh->lastInsertId($seq);
    }


    function sql_for_unixtime ( )
    {
        return "TRUNC(EXTRACT('epoch' FROM NOW( )))";
    }


    function bulk_insert ($skinny, $table, $args)
    {
        try {
            $skinny->dbh->beginTransaction( );

            foreach ($args as $arg) {
                $skinny->insert($table, $arg);
            }

            $skinny->dbh->commit( );
        }
        catch (Exception $e) {
            $skinny->dbh->rollback( );
            throw new Exception($e->getMessage( ));
        }

        return true;
    }
}
