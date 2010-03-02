<?php  // vim: ts=4 sts=4 sw=4
require_once 'PHPUnit/Framework.php';

set_include_path('./lib');
require_once 'Skinny/Profiler.php';

class TestSkinnyProfiler extends PHPUnit_Framework_TestCase
{
    private $class;

    function setUp ( )
    {
        $this->class = new SkinnyProfiler(1);
    }

    function testRecordQuery ( )
    {
        $profiler = $this->class;

        $query = 'SELECT * FROM user';
        $expect[ ] = $query;
        $profiler->record_query($query);
        $this->assertEquals($expect, $profiler->query_log);

        $query = '
            SELECT
                id, name
            FROM
                user
            WHERE
                name like "%neko%"
        ';
        $expect[ ] = 'SELECT id, name FROM user WHERE name like "%neko%"';
        $profiler->record_query($query);
        $this->assertEquals($expect, $profiler->query_log);

        $profiler->reset( );
        $expect = array( );
        $this->assertEquals($expect, $profiler->query_log);
    }

    function testBindValues ( )
    {
        $profiler = $this->class;

        $query = 'SELECT id FROM user WHERE id = ?';
        $bind  = array(1);
        $expect[ ] = $query.' :binds 1';
        $profiler->record_query($query, $bind);
        $this->assertEquals($expect, $profiler->query_log);

        $query = 'SELECT id FROM user WHERE (id = ? OR id = ?)';
        $bind  = array(1, 2);
        $expect[ ] = $query.' :binds 1, 2';
        $profiler->record_query($query, $bind);

        $query = '
            INSERT INTO user (name) VALUES (?)
        ';
        $expect[ ] = 'INSERT INTO user (name) VALUES (?) :binds null';
        $profiler->record_query($query, array(null));

        $this->assertEquals($expect, $profiler->query_log);
    }

    function testBindNamedValues ( )
    {
        $profiler = $this->class;

        $query = 'SELECT id FROM user WHERE id = :id';
        $bind  = array( ':id' => 1);
        $expect[ ] = $query.' :binds id => 1';
        $profiler->record_query($query, $bind);
        $this->assertEquals($expect, $profiler->query_log);

        $query = 'SELECT id FROM user WHERE (id = :id1 OR id = :id2)';
        $bind  = array(':id1' => 1, ':id2' => null);
        $expect[ ] = $query.' :binds id1 => 1, id2 => null';
        $profiler->record_query($query, $bind);

        $query = '
            INSERT INTO user (name) VALUES (?)
        ';
        $expect[ ] = 'INSERT INTO user (name) VALUES (?) :binds null';
        $profiler->record_query($query, array(null));

        $this->assertEquals($expect, $profiler->query_log);
    }
}
