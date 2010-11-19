<?php
require_once 'Skinny/SQL.php';


abstract class SkinnyDriverBase
{
    abstract function dbd_type ( );

    function query_builder_class ( ) { return 'SkinnySQL'; }
    function quote               ( ) { return '"'; }
    function name_sep            ( ) { return '.'; }
    function sql_for_unixtime    ( ) { return time( ); }


    function last_insert_id ($skinny, $table)
    {
        return $skinny->dbh( )->lastInsertId( );
    }


    function bulk_insert ($skinny, $table, $args)
    {
        $skinny->is_error  = false;
        $skinny->error_msg = '';

        $cols = array_keys($args[0]);
        $sql  = "INSERT INTO $table\n";
        $sql .= '(' . implode(', ', $cols) . ')' . "\n";
        $sql .= 'VALUES (' . implode(', ', array_fill(0, sizeof($cols), '?')) . ')';

        try {
            $txn = $skinny->txn_scope( );
            $sth = $skinny->dbh->prepare($sql);

            foreach ($args as $arg) {
                $bind = array( );
                foreach ($cols as $col) {
                    $value   = isset($arg[$col]) ? $arg[$col] : null;
                    $bind[ ] = $skinny->schema->call_deflate($col, $value);
                }

                $skinny->profiler($sql, $bind);
                $sth->execute( array_values($bind) );
            }

            $txn->commit( );
        }
        catch (Exception $e) {
            $txn->rollback( );
            $skinny->stack_trace($sth, $stmt, $bind, $e->getMessage( ));
            return false;
        }

        return true;
    }
}
