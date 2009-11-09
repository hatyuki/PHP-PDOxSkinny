<?php  // vim: ts=4 sts=4 sw=4
require_once 'Skinny.php';


// SkinnyProfiler based on DBIx::Skinny 0.04
class SkinnyProfiler
{
    public  $query_log = array( );  // -- Array
    private $mode      = 0;         // -- Int


    function __construct ($mode=0)
    {
        $this->mode = intval($mode);
        $this->reset( );
    }


    function reset ( )
    {
        $this->query_log = array( );
    }


    function record_query ($sql, $bind=array( ))
    {
        $log  = $this->normalize($sql);

        if ( sizeof($bind) != 0) {
            $log .= ' :binds '.join(', ', array_map(
                array($this, 'join_bind'), $bind, array_keys($bind)
            ) );
        }

        if (Skinny::TRACE_LOG & $this->mode) {
            $this->query_log[ ] = $log;
        }
        if (Skinny::PRINT_LOG & $this->mode) {
            print '[DEBUG] '.$log."\n";
        }
        if (Skinny::WRITE_LOG & $this->mode) {
            $date     = date('[Y-m-d H:i:s] ');
            $log_file = $_SERVER['SKINNY_LOG']
                      ? $_SERVER['SKINNY_LOG']
                      : getcwd( ).'/database.log';

            error_log($date.$log."\n", 3, $log_file);
        }
    }


    private function normalize ($sql)
    {
        $sql = preg_replace('/^\s*/',    '', $sql);
        $sql = preg_replace('/\s*$/',    '', $sql);
        $sql = preg_replace('/[\r\n]/', ' ', $sql);
        $sql = preg_replace('/\s+/',    ' ', $sql);

        return $sql;
    }


    private function join_bind ($val, $key)
    {
        $val = is_null($val) ? 'null' : $val;

        return is_integer($key)
             ? $val
             : preg_replace('/^:/', '', $key)." => $val";
    }
}
