<?php  // vim: ts=4 sts=4 sw=4
require_once 'Skinny/SQL.php';


// SkinnyDriver based on DBIx::Skinny 0.04
class SkinnyDriverMySQL
{
    function dbd_type            ( ) { return 'MySQL'; }
    function query_builder_class ( ) { return 'SkinnySQL'; }
    function quote               ( ) { return '`'; }
    function name_sep            ( ) { return '.'; }
    function sql_for_unixtime    ( ) { return "UNIX_TIMESTAMP( )"; }


    function last_insert_id ($skinny, $table)
    {
        return $skinny->dbh( )->lastInsertId( );
    }


    function bulk_insert ($skinny, $table)
    {
        try {
            $skinny->dbh( )->beginTransaction( );

            foreach ($args as $arg) {
                $skinny->insert($table, $arg);
            }

            $skinny->dbh( )->commit( );
        }
        catch (Exception $e) {
            $skinny->dbh( )->rollback( );
            throw new Exception($e->getMessage( ));
        }

        return true;
    }
}
