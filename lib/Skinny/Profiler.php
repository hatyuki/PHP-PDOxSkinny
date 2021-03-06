<?php
require_once 'Skinny.php';


// SkinnyProfiler based on DBIx::Skinny 0.04
class SkinnyProfiler
{
    public    $query_log = array( );  // -- Array
    protected $mode      = 0;         // -- Int
    protected $logfile   = null;


    function __construct ($mode=0, $logfile=null)
    {
        $this->mode    = $this->mode2number($mode);
        $this->logfile = $logfile;
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

        if (Skinny::TRACE_LOG & $this->mode) {
            $this->query_log[ ] = $log;
        }
        if (Skinny::PRINT_LOG & $this->mode) {
            print '[DEBUG] '.$log."\n";
        }
        if (Skinny::WRITE_LOG & $this->mode && $this->logfile) {
            $pid  = getmypid( );
            $date = date("[Y-m-d H:i:s|$pid] ");

            error_log($date.$log."\n", 3, $this->logfile);
        }
    }


    protected function normalize ($sql)
    {
        $sql = preg_replace('/^\s*/',    '', $sql);
        $sql = preg_replace('/\s*$/',    '', $sql);
        $sql = preg_replace('/[\r\n]/', ' ', $sql);
        $sql = preg_replace('/\s+/',    ' ', $sql);

        return $sql;
    }


    protected function join_bind ($val, $key)
    {
        $val = is_null($val) ? 'null' : $val;

        return is_integer($key)
             ? $val
             : preg_replace('/^:/', '', $key)." => $val";
    }


    protected function mode2number ($str)
    {
        if ( is_numeric($str) ) {
            return intval($str);
        }

        $mode = explode(',', $str);
        $retval = 0;

        foreach ($mode as $m) {
            switch ($m) {
            case preg_match('/TRACE_LOG/i', $m) > 0:
                $retval += Skinny::TRACE_LOG;
                break;

            case preg_match('/PRINT_LOG/i', $m) > 0:
                $retval += Skinny::PRINT_LOG;
                break;

            case preg_match('/WRITE_LOG/i', $m) > 0:
                $retval += Skinny::WRITE_LOG;
                break;
            }
        }

        return $retval;
    }
}
