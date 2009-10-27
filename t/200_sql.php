<?php  // vim: ts=4 sts=4 sw=4
require_once 'PHPUnit/Framework.php';

set_include_path('./lib');
require_once 'Skinny/SQL.php';

class TestSkinnySQL extends PHPUnit_Framework_TestCase
{
    private $class;

    function setUp ( )
    {
        $this->class = new SkinnySQL( );
    }

    function testFrom ( )
    {
        $obj =& $this->class;

        $obj->from('foo');
        $this->assertEquals("FROM foo\n", $obj->as_sql( ));

        $obj->from( array('foo') );
        $this->assertEquals("FROM foo\n", $obj->as_sql( ));

        $obj->from( array('foo', 'bar') );
        $this->assertEquals("FROM foo, bar\n", $obj->as_sql( ));
    }

    function testJoin ( )
    {
        $obj =& $this->class;

        $join = array(
            'foo' => array(
                array(
                    'type'      => 'inner',
                    'table'     => 'baz b1',
                    'condition' => 'foo.baz_id = b1.baz_id AND b1.quux_id = 1',
                ),
                array(
                    'type'      => 'left',
                    'table'     => 'baz b2',
                    'condition' => 'foo.baz_id = b2.baz_id AND b2.quux_id = 2',
                ),
             ),
        );
        $obj->add_join($join);

        $res = ''
             .'FROM foo '
             .'INNER JOIN baz b1 ON foo.baz_id = b1.baz_id AND b1.quux_id = 1 '
             .'LEFT JOIN baz b2 ON foo.baz_id = b2.baz_id AND b2.quux_id = 2';

        $this->assertEquals($res."\n", $obj->as_sql( ));
    }

    function testJoinTwice ( )
    {
        $obj =& $this->class;

        $join = array(
            'foo' => array(
                array(
                    'type'      => 'inner',
                    'table'     => 'baz b1',
                    'condition' => 'foo.baz_id = b1.baz_id AND b1.quux_id = 1',
                ),
            ),
        );
        $obj->add_join($join);

        $join = array(
            'foo' => array(
                array(
                    'type'      => 'left',
                    'table'     => 'baz b2',
                    'condition' => 'foo.baz_id = b2.baz_id AND b2.quux_id = 2',
                ),
             ),
        );
        $obj->add_join($join);

        $res = ''
             .'FROM foo '
             .'INNER JOIN baz b1 ON foo.baz_id = b1.baz_id AND b1.quux_id = 1 '
             .'LEFT JOIN baz b2 ON foo.baz_id = b2.baz_id AND b2.quux_id = 2';
        $this->assertEquals($res."\n", $obj->as_sql( ));


        # test case adding another table onto the whole mess
        $join = array(
            'quux' => array(
                array(
                    'type'      => 'inner',
                    'table'     => 'foo f1',
                    'condition' => 'f1.quux_id = quux.q_id',
                ),
            ),
        );
        $obj->add_join($join);
        $res = ''
            .'FROM foo '
            .'INNER JOIN baz b1 ON foo.baz_id = b1.baz_id AND b1.quux_id = 1 '
            .'LEFT JOIN baz b2 ON foo.baz_id = b2.baz_id AND b2.quux_id = 2 '
            .'INNER JOIN foo f1 ON f1.quux_id = quux.q_id';
        $this->assertEquals($res."\n", $obj->as_sql( ));
    }

    function testGroupBy ( )
    {
        $obj =& $this->class;

        $obj->from('foo');
        $obj->group( array('column' => 'baz') );
        $this->assertEquals("FROM foo\nGROUP BY baz\n", $obj->as_sql( ));

        $obj->from('foo');
        $obj->group( array('column' => 'baz', 'desc' => 'DESC') );
        $this->assertEquals("FROM foo\nGROUP BY baz DESC\n", $obj->as_sql( ));

        $obj->from('foo');
        $obj->group( array(
            array('column' => 'baz'),
            array('column' => 'quux'),
        ) );
        $this->assertEquals("FROM foo\nGROUP BY baz, quux\n", $obj->as_sql( ));

        $obj->from('foo');
        $obj->group( array(
            array('column' => 'baz',  'desc' => 'DESC'),
            array('column' => 'quux', 'desc' => 'DESC'),
        ) );
        $this->assertEquals("FROM foo\nGROUP BY baz DESC, quux DESC\n", $obj->as_sql( ));
    }

    function testOrderBy ( )
    {
        $obj =& $this->class;

        $obj->from('foo');
        $obj->order( array('column' => 'baz', 'desc' => 'DESC') );
        $this->assertEquals("FROM foo\nORDER BY baz DESC\n", $obj->as_sql( ));

        $obj->from('foo');
        $obj->order( array(
            array('column' => 'baz', 'desc' => 'DESC'),
            array('column' => 'quux', 'desc' => 'ASC'),
        ) );
        $this->assertEquals("FROM foo\nORDER BY baz DESC, quux ASC\n", $obj->as_sql( ));
    }

    function testGroupByPlusOrderBy ( )
    {
        $obj =& $this->class;

        $obj->from('foo');
        $obj->group( array('column' => 'quux') );
        $obj->order( array('column' => 'baz', 'desc' => 'DESC') );
        $this->assertEquals("FROM foo\nGROUP BY quux\nORDER BY baz DESC\n", $obj->as_sql( ));
    }

    function testLimitOffset ( )
    {
        $obj =& $this->class;

        $obj->from('foo');
        $obj->limit(5);
        $this->assertEquals("FROM foo\nLIMIT 5\n", $obj->as_sql( ));
        
        $obj->offset(10);
        $this->assertEquals("FROM foo\nLIMIT 5 OFFSET 10\n", $obj->as_sql( ));
    }

    function testWhere1 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => 'bar') );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo = ?)\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 1);
        $this->assertEquals($bind[0], 'bar');
    }

    function testWhere2 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => array('bar', 'baz')) );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo IN (?, ?))\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 2);
        $this->assertEquals($bind[0], 'bar');
        $this->assertEquals($bind[1], 'baz');
    }

    function testWhere3 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => array('in' => array('bar', 'baz') ) ) );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo IN (?, ?))\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 2);
        $this->assertEquals($bind[0], 'bar');
        $this->assertEquals($bind[1], 'baz');
    }

    function testWhere4 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => array('not in' => array('bar', 'baz') ) ) );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo NOT IN (?, ?))\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 2);
        $this->assertEquals($bind[0], 'bar');
        $this->assertEquals($bind[1], 'baz');
    }

    function testWhere5 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => array('!=' => 'bar') ) );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo != ?)\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 1);
        $this->assertEquals($bind[0], 'bar');
    }

    function testWhere6 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => array('inject' => 'IS NOT NULL') ) );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo IS NOT NULL)\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 0);
    }

    function testWhere7 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => 'bar') );
        $obj->add_where( array('baz' => 'quux') );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo = ?) AND (baz = ?)\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 2);
        $this->assertEquals($bind[0], 'bar');
        $this->assertEquals($bind[1], 'quux');
    }

    function testWhere8 ( ) {
        $obj =& $this->class;

        $obj->add_where(
            array('foo' => array(
                    array('>' => 'bar'),
                    array('<' => 'baz'),
                ),
            )
        );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE ((foo > ?) OR (foo < ?))\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 2);
        $this->assertEquals($bind[0], 'bar');
        $this->assertEquals($bind[1], 'baz');
    }

    function testWhere9 ( )
    {
        $obj =& $this->class;

        $obj->add_where(
            array('foo' => array(
                '-and',
                array('>' => 'bar'),
                array('<' => 'baz'),
            ) )
        );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE ((foo > ?) AND (foo < ?))\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 2);
        $this->assertEquals($bind[0], 'bar');
        $this->assertEquals($bind[1], 'baz');
    }

    function testWhere10 ( )
    {
        $obj =& $this->class;

        $obj->add_where(
            array('foo' => array(
                '-and', 'foo', 'bar', 'baz',
            ) )
        );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE ((foo = ?) AND (foo = ?) AND (foo = ?))\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 3);
        $this->assertEquals($bind[0], 'foo');
        $this->assertEquals($bind[1], 'bar');
        $this->assertEquals($bind[2], 'baz');
    }

    function testModifiedParameters1 ( )
    {
        $obj =& $this->class;

        $terms = array('foo' => array('-and', 'foo', 'bar', 'baz'));
        $obj->add_where($terms);
        $this->assertEquals("WHERE ((foo = ?) AND (foo = ?) AND (foo = ?))\n", $obj->as_sql_where( ));
        $obj->add_where($terms);
        $this->assertEquals("WHERE ((foo = ?) AND (foo = ?) AND (foo = ?)) AND ((foo = ?) AND (foo = ?) AND (foo = ?))\n", $obj->as_sql_where( ));
    }

    function testModifiedParameters2 ( )
    {
        $obj =& $this->class;

        $obj->add_select( array('foo' => 'foo') );
        $obj->add_select('bar');
        $obj->from( array('baz') );
        $this->assertEquals("SELECT foo, bar\nFROM baz\n", $obj->as_sql( ));
    }

    function testModifiedParameters3 ( )
    {
        $obj =& $this->class;

        $obj->add_select( array('f.foo' => 'foo') );
        $obj->add_select( array('COUNT(*)' => 'count') );
        $obj->from( array('baz') );
        $this->assertEquals("SELECT f.foo, COUNT(*) AS count\nFROM baz\n", $obj->as_sql( ));

        $map = $obj->select_map( );
        $this->assertEquals(sizeof(array_keys($map)), 2);
        $this->assertEquals('foo', $map['f.foo']);
        $this->assertEquals('count', $map['COUNT(*)']);
    }

    function testOptionalWhere1 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => array('between' => array(1, 100))) );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo BETWEEN (? AND ?))\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 2);
        $this->assertEquals($bind[0], 1);
        $this->assertEquals($bind[1], 100);
    }

    function testOptionalWhere2 ( )
    {    
        $obj =& $this->class;

        $obj->add_where( array('foo' => array('like' => 'baz')) );
        $bind = $obj->bind( );

        $this->assertEquals("WHERE (foo LIKE ?)\n", $obj->as_sql_where( ));
        $this->assertEquals(sizeof($bind), 1);
        $this->assertEquals($bind[0], 'baz');
    }

    function testHaving ( )
    {
        $obj =& $this->class;

        $obj->add_select('foo');
        $obj->add_select( array('COUNT(*)' => 'count') );
        $obj->from('baz');
        $obj->add_where( array('foo' => 1) );
        $obj->group( array('column' => 'baz') );
        $obj->order( array( array('column' => 'foo', 'desc' => 'DESC') ) );
        $obj->limit(2);
        $obj->add_having( array('count' => 2) );

        $res = ''
            ."SELECT foo, COUNT(*) AS count\n"
            ."FROM baz\n"
            ."WHERE (foo = ?)\n"
            ."GROUP BY baz\n"
            ."HAVING (COUNT(*) = ?)\n"
            ."ORDER BY foo DESC\n"
            ."LIMIT 2\n";

        $this->assertEquals($res, $obj->as_sql( ));
    }

    function testDistinct ( )
    {
        $obj =& $this->class;

        $obj->add_select( array('foo' => 'foo') );
        $obj->from('baz');
        $this->assertEquals("SELECT foo\nFROM baz\n", $obj->as_sql( ));

        $obj->distinct(1);
        $this->assertEquals("SELECT DISTINCT foo\nFROM baz\n", $obj->as_sql( ));
    }

    function testComment ( )
    {
        $obj =& $this->class;

        $obj->add_select( array('foo' => 'foo') );
        $obj->from('baz');
        $obj->comment('mycomment');
        $this->assertEquals("SELECT foo\nFROM baz\n-- mycomment", $obj->as_sql( ));

        $obj->comment("\nbad\n\nmycomment");
        $this->assertEquals("SELECT foo\nFROM baz\n-- bad", $obj->as_sql( ));

        $obj->comment("G\\G");
        $this->assertEquals("SELECT foo\nFROM baz\n-- G", $obj->as_sql( ));
    }
}
