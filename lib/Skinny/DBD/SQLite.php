<?php
require_once 'Skinny/DBD/Base.php';


// SkinnyDriver based on DBIx::Skinny 0.04
class SkinnyDriverSQLite extends SkinnyDriverBase
{
    function dbd_type         ( ) { return 'SQLite'; }
    function quote            ( ) { return '`'; }
    function name_sep         ( ) { return '.'; }
    function sql_for_unixtime ( ) { return time( ); }
}
