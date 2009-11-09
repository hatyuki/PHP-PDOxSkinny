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
        $log = $this->normalize($sql);

        if ( sizeof($bind) != 0) {
            $log .= ' :binds '.join(', ', array_map(
                array($this, 'join_bind'), $bind, array_keys($bind)
            ) );
        }

        if (Skinny::LOG_TRACE & $this->mode) {
            $this->query_log[ ] = $log;
        }
        if (Skinny::LOG_PRINT & $this->mode) {
            print $log."\n";
        }
        if (Skinny::LOG_WRITE & $this->mode) {
            $log_file = $_SERVER['SKINNY_LOG']
                      ? $_SERVER['SKINNY_LOG']
                      : getcwd( ).'/database.log';

            error_log($log, 3, $log_file);
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
