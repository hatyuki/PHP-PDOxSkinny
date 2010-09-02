<?php
require_once 'Cache/Lite.php';
require_once 'Skinny/Mixin.php';

class SkinnyMixinCacheLite extends SkinnyMixin
{
    protected $cache     = null;
    protected $group     = null;
    protected $cache_hit = false;


    function __construct ($skinny, $options=array( ))
    {
        $this->cache = new Cache_Lite($options);
        $this->group = isset($options['group'])
                     ? $options['group']
                     : sha1( mt_rand( ) );

        parent::__construct($skinny);
    }


    function register_method ( )
    {
        return array(
            'search_by_sql_with_cache' => array($this, 'search_by_sql_with_cache'),
            'cache_hit'                => array($this, 'cache_hit'),
        );
    }


    function search_by_sql_with_cache ($sql, $bind=array( ), $opt_table_info=null)
    {
        $profiler = $this->skinny->profiler( );
        $hash     = $this->serialize_statement($sql, $bind);

        if ( ($cache = $this->cache->get($hash, $this->group)) ) {
            $cache = unserialize($cache);
            $itr   = $this->skinny->data2itr($cache['table'], $cache['data']);

            $this->cache_hit = true;
            $profiler->record_query("SkinnyMixinCacheLite -- Hit: $hash");
        }
        else {
            $itr   = $this->skinny->search_by_sql($sql, $bind, $opt_table_info);
            $cache = array(
                'table' => $itr->opt_table_info,
                'data'  => $itr->all_as_hash( ),
            );

            $this->cache->save(serialize($cache), $hash, $this->group);

            $this->cache_hit = false;
            $profiler->record_query("SkinnyMixinCacheLite -- Miss: $hash");
        }

        $itr->reset( );

        return $itr;
    }


    function cache_hit ( )
    {
        return $this->cache_hit;
    }


    protected function serialize_statement ($sql, $bind)
    {
        $stmt = $sql.' / '.serialize($bind);

        return sha1($stmt);
    }
}
