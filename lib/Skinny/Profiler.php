<?php  // vim: ts=4 sts=4 sw=4


// SkinnyProfiler based on DBIx::Skinny 0.04
class SkinnyProfiler
{
    public $query_log = array( );


    function __construct ( )
    {
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

        $this->query_log[ ] = $log;
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
