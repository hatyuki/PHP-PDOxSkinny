<?php
set_include_path(get_include_path( ).':./lib:./t');
require_once 'Mock/Basic.php';
require_once 'Skinny/Mixin/CacheLite.php';


class TestMixinCacheLite extends PHPUnit_Framework_TestCase
{
    private $class;

    function setUp ( )
    {
        mkdir('./t/cache');

        $this->class = new MockBasic( array(
            'dsn'      => 'sqlite:./t/main.db',
            'username' => '',
            'password' => '',
        ) );
        $this->class->setup_test_db( );
        $this->class->insert('mock_basic', array(
            'id'   => 1,
            'name' => 'perl',
        ) );
        $this->class->insert('mock_basic', array(
            'id'   => 2,
            'name' => 'python',
        ) );
    }

    function tearDown ( )
    {
        $this->remove_directory('./t/cache');
        unlink('./t/main.db');
    }

    function remove_directory($dir) {
        if ($handle = opendir("$dir")) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dir/$item")) {
                        remove_directory("$dir/$item");
                    } else {
                        unlink("$dir/$item");
                    }
                }
            }
            closedir($handle);
            rmdir($dir);
        }
    }


    function testNewUsingObject ( )
    {
        $cache = new SkinnyMixinCacheLite($this->class, array(
            'cacheDir' => './t/cache/',
            'lifeTime' => 3,
        ) );
        $this->class->mixin($cache);

        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(1)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
        $this->assertFalse($this->class->cache_hit( ));
    }


    function testNewWithConfig ( )
    {
        $cache_config = array(
            'cacheDir' => './t/cache/',
            'lifeTime' => 3,
        );
        $this->class->mixin( array(
            'SkinnyMixinCacheLite' => $cache_config,
        ) );

        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(2)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 2);
        $this->assertEquals($row->name, 'python');
        $this->assertFalse($this->class->cache_hit( ));
    }


    function testCacheSearch ( )
    {
        $cache_config = array(
            'cacheDir' => './t/cache/',
            'lifeTime' => 3,
        );
        $this->class->mixin( array(
            'SkinnyMixinCacheLite' => $cache_config,
        ) );


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(1)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
        $this->assertFalse($this->class->cache_hit( ));


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(1)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
        $this->assertTrue($this->class->cache_hit( ));


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(2)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 2);
        $this->assertEquals($row->name, 'python');
        $this->assertFalse($this->class->cache_hit( ));


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(1)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
        $this->assertTrue($this->class->cache_hit( ));


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(2)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 2);
        $this->assertEquals($row->name, 'python');
        $this->assertTrue($this->class->cache_hit( ));
    }


    function testCacheTimeout ( )
    {
        $cache_config = array(
            'cacheDir' => './t/cache/',
            'lifeTime' => 3,
        );
        $this->class->mixin( array(
            'SkinnyMixinCacheLite' => $cache_config,
        ) );


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(1)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
        $this->assertFalse($this->class->cache_hit( ));


        sleep(4);


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(1)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
        $this->assertFalse($this->class->cache_hit( ));


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(1)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'perl');
        $this->assertTrue($this->class->cache_hit( ));


        $itr = $this->class->search_by_sql_with_cache(
            'SELECT * FROM mock_basic WHERE id = ?', array(2)
        );
        $this->assertTrue( is_a($itr, 'SkinnyIterator') );
        $row = $itr->first( );
        $this->assertTrue( is_a($row, 'SkinnyRow') );
        $this->assertEquals($row->id, 2);
        $this->assertEquals($row->name, 'python');
        $this->assertFalse($this->class->cache_hit( ));
    }
}
