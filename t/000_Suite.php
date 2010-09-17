<?php
require_once 'PHPUnit/Framework/TestSuite.php';

class TestSkinnySuite
{
    public static function suite ( )
    {
        $suite = new PHPUnit_Framework_TestSuite( );

        include_once '001_compile.php';
        $suite->addTestSuite('TestSkinnyCompile');

        include_once '002_new.php';
        $suite->addTestSuite('TestSkinnyNew');

        include_once '101_insert.php';
        $suite->addTestSuite('TestSkinnyInsert');

        include_once '102_update.php';
        $suite->addTestSuite('TestSkinnyUpdate');

        include_once '103_delete.php';
        $suite->addTestSuite('TestSkinnyDelete');

        include_once '104_find_or_create.php';
        $suite->addTestSuite('TestSkinnyFindOrCreate');

        include_once '105_count.php';
        $suite->addTestSuite('TestSkinnyCount');

        include_once '106_inflate.php';
        $suite->addTestSuite('TestSkinnyInflate');

        include_once '108_trigger.php';
        $suite->addTestSuite('TestSkinnyTrigger');

        include_once '200_sql.php';
        $suite->addTestSuite('TestSkinnySQL');

        include_once '202_profiler.php';
        $suite->addTestSuite('TestSkinnyProfiler');

        include_once '213_iterator.php';
        $suite->addTestSuite('TestSkinnyIterator');

        include_once '900_mixin_cache_lite.php';
        $suite->addTestSuite('TestMixinCacheLite');

        return $suite;
    }
}
