<?php  // vim: ts=4 sts=4 sw=4
require_once 'Skinny/Profiler.php';
require_once 'Skinny/Iterator.php';
require_once 'Skinny/Transaction.php';


class Skinny
{
    // for SkinnyProfiler
    const LOG_TRACE = 1;
    const LOG_PRINT = 2;
    const LOG_WRITE = 4;
}


// PDOxSkinny based on DBIx::Skinny 0.04
class PDOxSkinny
{
    public $VERSION = 0.04;

    private $dsn                = null;      // -- String
    private $username           = null;      // -- String
    private $password           = null;      // -- String
    private $connect_options    = array( );  // -- Hash
    private $dbh                = null;      // -- Object[PDO]
    private $dbd                = null;      // -- Object
    private $schema             = null;      // -- Object[SkinnySchema]
    private $profiler           = null;      // -- Object[SkinnyProfiler]
    private $profile            = false;     // -- Bool
    private $row_class_map      = array( );  // -- Hash
    private $active_transaction = false;     // -- Bool
    private $mixins             = array( );  // -- Hash


    function __construct ($args=array( ))
    {
        $schema  = get_class($this).'Schema';
        $this->active_transaction = false;
        $this->schema             = new $schema;

        if ( is_a($args, 'PDOxSkinny') ) {
            $this->dbh      = $args->dbh( );
            $this->dbd      = $args->dbd( );
            $this->profile  = $args->profile( );
            $this->profiler = new SkinnyProfiler($this->profile);
        }
        else if ( !empty($args) ) {
            $profile = $args['profile']
                     ? $args['profile']
                     : $_SERVER['SKINNY_PROFILE'];
            $this->profile  = $profile;
            $this->profiler = new SkinnyProfiler($this->profile);

            $this->connect_info($args);
            $this->reconnect( );
        }
    }


    /* ---------------------------------------------------------------
     *  Reader
     */
    function dbh        ( ) { return $this->dbh; }
    function dbd        ( ) { return $this->dbd; }
    function schema     ( ) { return $this->schema; }
    function query_log  ( ) { return $this->profiler->query_log; }
    function txn_status ( ) { return $this->active_transaction; }
    function profile    ( ) { return $this->profile; }


    /* ---------------------------------------------------------------
     *  Profile
     */
    function profiler ($sql, $bind=array( ))
    {
        if ($this->profile && $sql) {
            $this->profiler->record_query($sql, $bind);
        }

        return $this->profiler;
    }


    /* ---------------------------------------------------------------
     *  Transaction Support
     */
    function txn_scope ( )
    {
        if ( $this->active_transaction ) {
            trigger_error(
                "The 'txn_scope' method can not be performed during a transaction.",
                E_USER_ERROR
            );
        }

        return new SkinnyTransaction($this);
    }


    function txn_begin ( )
    {
        $this->active_transaction = true;

        try {
            $this->dbh->beginTransaction( );
        } catch (Exception $e) {
            trigger_error($e->getMessage( ), E_USER_ERROR);
        }

        return $this;
    }


    function txn_rollback ( )
    {
        if ( !$this->active_transaction ) {
            return false;
        }

        try {
            $this->dbh->rollback( );
        } catch (Exception $e) {
            trigger_error($e->getMessage( ), E_USER_ERROR);
        }

        return $this->txn_end( );
    }


    function txn_commit ( )
    {
        if ( !$this->active_transaction ) {
            return false;
        }

        try {
            $this->dbh->commit( );
        } catch (Exception $e) {
            trigger_error($e->getMessage( ), E_USER_ERROR);
        }

        return $this->txn_end( );
    }


    function txn_end ( )
    {
        $this->active_transaction = false;

        return $this;
    }


    /* ---------------------------------------------------------------
     *  DB Handling
     */
    function connect_info ($connect_info)
    {
        $this->dsn             = $connect_info['dsn'];
        $this->username        = $connect_info['username'];
        $this->password        = $connect_info['password'];
        $this->connect_options = $connect_info['connect_options'];

        return $this;
    }


    function connect ($connect_info=array( ))
    {
        if ( !empty($connect_info) ) {
            $this->connect_info($connect_info);
        }

        if ( empty($this->dbh) ) {
            $dbd_type = $this->dbd_type($this->dsn);
            require_once "Skinny/DBD/$dbd_type.php";
            $dbd = 'SkinnyDriver'.$dbd_type;

            try {
                $this->dbd = new $dbd( );
                $this->dbh = new PDO($this->dsn, $this->username, $this->password);
                $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $auto_commit = $this->connect_options['AutoCommit']
                    ? true
                    : false;

                $on_connect_do = is_array($this->connect_options['on_connect_do'])
                    ? $this->connect_options['on_connect_do']
                    : array($this->connect_options['on_connect_do']);

                if ($auto_commit) {
                    $this->dbh->setAttribute(PDO::ATTR_AUTOCOMMIT, $auto_commit);
                }

                foreach ($on_connect_do as $sql) {
                    if ( empty($sql) ) {
                        continue;
                    }

                    $this->query($sql);
                }
            }
            catch (Exception $e) {
                trigger_error($e->getMessage( ), E_USER_ERROR);
            }
        }

        return $this->dbh;
    }


    function reconnect ($connect_info=array( ))
    {
        $this->dbh = null;
        $this->dbd = null;
        return $this->connect($connect_info);
    }


    function close_sth ($sth)
    {
        $sth->closeCursor( );
        $sth = null;
    }


    /* ---------------------------------------------------------------
     * Schema Trigger Call
     */
    function call_schema_trigger ($trigger, $schema, &$table, &$args=array( ))
    {
        $schema->call_trigger($this, &$table, $trigger, &$args);
    }


    /* ---------------------------------------------------------------
     * Executor
     */
    function query ($sql)  // do method on DBIx::Skinny
    {
        if ( empty($sql) ) {
            return null;
        }

        $this->profiler($sql);

        return $this->dbh->query($sql);
    }


    function count ($table, $column='*', $where=array( ))
    {
        $rs = $this->resultset( array('from' => $table) );

        $rs->add_select( array("COUNT($column)" => 'cnt') );
        $this->add_where($rs, $where);

        return $rs->retrieve( )->first( )->cnt( );
    }


    function resultset ($args=array( ))
    {
        $args['skinny'] = $this;

        $query_builder_class = $this->dbd->query_builder_class( );

        return new $query_builder_class($args);
    }


    function search ($table=null, $where=array( ), $opt=array( ))
    {
        $cols = $opt['select']
              ? $opt['select']
              : $this->schema->schema_info[$table]['columns'];

        if ( empty($cols) ) {
            $cols = array('*');
        }

        $rs = $this->resultset( array(
            'select' => $cols,
            'from'   => empty($table) ? null : $table,
        ) );

        if ( !empty($where) ) {
            $this->add_where($rs, $where);
        }

        if ( $opt['limit'] ) {
            $rs->limit( $opt['limit'] );
        }
        if ( $opt['offset'] ) {
            $rs->offset( $opt['offset'] );
        }

        if ( $terms = $opt['order_by'] ) {
            if ($this->ref($terms) != 'ARRAY') {
                $terms = array($terms);
            }

            $orders = array( );

            foreach ($terms as $term) {
                if ($this->ref($term) == 'HASH') {
                    list($col, $case) = each($term);
                }
                else {
                    $col  = $term;
                    $case = 'ASC';
                }

                $orders[ ] = array('column' => $col, 'desc' => $case);
            }

            $rs->order($orders);
        }

        if ( $terms = $opt['having'] ) {
            foreach ($terms as $col => $val) {
                $rs->add_having( array($col => $val) );
            }
        }

        return $rs->retrieve( );
    }


    function single ($table, $where=array( ), $opt=array( ))
    {
        $opt['limit'] = 1;

        return $this->search($table, $where, $opt)->first( );
    }


    function search_by_sql ($sql, $bind=array( ), $opt_table_info=null)
    {
        $this->profiler($sql, $bind);

        $sth = $this->execute($sql, $bind);

        return $this->get_sth_iterator($sql, $sth, $opt_table_info);
    }


    function find_or_new ($table, $args)
    {
        $row = $this->single($table, $args);

        if ( !$row ) {
            $row = $this->data2itr($table, array($args))->first( );
        }

        return $row;
    }


    function data2itr ($table, $data)
    {
        return new SkinnyIterator( array(
            'skinny'         => $this,
            'data'           => $data,
            'opt_table_info' => $table,
        ) );
    }


    function create ($table, $args) { return $this->insert($table, $args); }
    function insert ($table, $args)
    {
        $schema = $this->schema;

        $this->call_schema_trigger('pre_insert', $schema, $table, $args);

        foreach ($args as $col => $val) {
            $args[$col] = $schema->call_deflate($col, $val);
        }

        $cols = array( );
        $bind = array( );

        foreach ($args as $col => $val) {
            $cols[ ] = $col;
            $bind[ ] = $val;
        }

        $sql  = "INSERT INTO $table\n";
        $sql .= '('.join(', ', array_map(array($this, 'quote'), $cols)).")\n";
        $sql .= 'VALUES ('.join(', ', array_fill(0, sizeof($cols), '?')).")\n";

        $this->profiler($sql, $bind);

        $sth = $this->execute($sql, $bind);
        $pk  = $this->schema->schema_info[$table]['pk'];
        $id  = isset($args[$pk]) ? $args[$pk] : $this->dbd->last_insert_id($this, $table);

        $this->close_sth($sth);

        $obj = $this->search($table, array(
            $schema->schema_info[$table]['pk'] => $id
        ) )->first( );

        $this->call_schema_trigger('post_insert', $schema, $table, $obj);

        return $obj;
    }


    function bulk_insert ($table, $args)
    {
        if ( method_exists($this->dbd, 'bulk_insert') ) {
            return $this->dbd->bulk_insert($table, $args);
        }
        else {
            trigger_error("dbd don't provide bulk_insert method", E_USER_ERROR);
        }
    }


    function update ($table, $args, $where)
    {
        $schema = $this->schema;
        $this->call_schema_trigger('pre_update', $schema, $table, $args);

        $values = array( );

        foreach ($args as $col => $val) {
            $values[$col] = $schema->call_deflate($col, $val);
        }

        $quote    = $this->dbd->quote( );
        $name_sep = $this->dbd->name_sep( );
        $set      = array( );
        $bind     = array( );

        foreach ($values as $col => $val) {
            $quoted_col = $this->quote($col, $quote, $name_sep);

            if ( $this->ref($val) == 'ARRAY' && array_key_exists('-inject', $val) ) {
                $set[ ] = "$quoted_col = $val";
            }
            else if ($this->ref($val) == 'SCALAR') {
                $set[ ]  = "$quoted_col = ?";
                $bind[ ] = $values;
            }
            else {
                $dump = print_r($val, true);
                trigger_error("could not parse value: $dump", E_USER_ERROR);
            }
        }

        $stmt = $this->resultset( );
        $this->add_where($stmt, $where);
        $bind = array_merge_recursive($bind, $stmt->bind( ));

        $sql = "UPDATE $table SET ".join(', ', $set).' '.$stmt->as_sql_where( );

        $this->profiler($sql, $bind);

        $sth  = $this->dbh->prepare($sql);
        $rows = $sth->execute($bind);

        $this->close_sth($sth);
        $this->call_schema_trigger('post_update', $schema, $table, $rows);

        return $rows;
    }


    function update_by_sql ($sql, $bind)
    {
        $this->profiler($sql, $bind);

        $sth  = $this->dbh->prepare($sql);
        $rows = $sth->execute($bind);

        $this->close_sth($sth);

        return $rows;
    }


    function delete ($table, $where)
    {
        $schema = $this->schema;

        $this->call_schema_trigger('pre_delete', $schema, $table, $where);

        $stmt = $this->resultset( array('from' => $table) );
        $this->add_where($stmt, $where);
        $sql  = 'DELETE '.$stmt->as_sql( );
        $this->profiler($sql, $stmt->bind( ));
        $sth  = $this->execute($sql, $stmt->bind( ));

        $this->call_schema_trigger('post_delete', $schema, $table);

        $ret = $sth->rowCount( );
        $this->close_sth($sth);

        return $ret;
    }


    function delete_by_sql ($sql, $bind)
    {
        $this->profiler($sql, $bind);
        $sth = $this->dbh->prepare($sql);
        $ret = $sth->execute($bind);
        $this->close_sth($sth);

        return $ret;
    }


    function find_or_insert ($table, $args) { return $thiis->find_or_create($table, $args); }
    function find_or_create ($table, $args)
    {
        $row = $this->single($table, $args);

        if ($row) {
            return $row;
        }

        return $this->insert($table, $args);
    }


    private function dbd_type ($dsn)
    {
        if ( !preg_match('/^(.+):/', $dsn, $m) ) {
            trigger_error('unknown db type', E_USER_ERROR);
        }

        switch ($m[1]) {
        case 'pgsql':
            return 'PostgreSQL';
        case 'mysql':
            return 'MySQL';
        case 'sqlite':
            return 'SQLite';
        default:
            trigger_error('No Driver: '.$m[1], E_USER_ERROR);
        }
    }


    private function get_sth_iterator ($sql, $sth, $opt_table_info)
    {
        return new SkinnyIterator( array(
            'skinny'         => $this,
            'sth'            => $sth,
            'opt_table_info' => $opt_table_info,
        ) );
    }


    private function quote ($label)
    {
        $quote    = $this->dbd->quote( );
        $name_sep = $this->dbd->name_sep( );

        if ($label == '*') {
            return $label;
        }

        if ( empty($name_sep) ) {
            return $quote.$label.$quote;
        }

        foreach (preg_split("/\Q$name_sep\E/", $label) as $l) {
            $labels[ ] = $quote.$l.$quote;
        }

        return join($name_sep, $labels);
    }


    private function add_where ($stmt, $where)
    {
        foreach ($where as $col => $val) {
            $stmt->add_where( array($col => $val) );
        }

        return $this;
    }


    private function execute ($stmt, $bind)
    {
        try {
            $sth = $this->dbh->prepare($stmt);
            $sth->execute($bind);
        }
        catch (Exception $e) {
            $this->stack_trace($sth, $stmt, $bind, $e);
        }

        return $sth;
    }


    private function stack_trace ($sth, $stmt, $bind, $reason)
    {
        if ($sth) {
            $this->close_sth($sth);
        }

        $reason = preg_replace("/\n/", "\n          ", $reason->getMessage( ));

        $stmt = preg_replace("/\n/", "\n          ", $stmt);

        $bind = print_r($bind, true);
        $bind = preg_replace("/\n/", "\n          ", $bind);

        $text = "Trace Error:
@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
@@@@@@  PDOxSkinny 's Exception  @@@@@@
Reason  : %s
SQL     : %s
BIND    : %s
@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
";
        $msg = sprintf($text, $reason, $stmt, $bind);

        trigger_error($msg, E_USER_ERROR);
    }


    private function ref ($val)
    {
        if ( !is_array($val) ) {
            return 'SCALAR';
        }
        else if ( is_array($val) ) {
            reset($val);

            foreach ($val as $k => $v) {
                if ( !is_integer($k) ) {
                    reset($val);
                    return 'HASH';
                }
            }

            return 'ARRAY';
        }

        return '';
    }


    /* ---------------------------------------------------------------
     *  Provides Mixin Function
     */
    function __call ($method, $args)
    {
        if ( array_key_exists($method, $this->mixins) ) {
            $func = $this->mixins[$method];
            return call_user_func($func, $args);
        }

        trigger_error('Call to undefinded function: '.$method, E_USER_ERROR);
    }


    function mixin ($include)
    {
        if ( !is_array($include) ) {
            $include = array($include);
        }

        foreach ($include as $obj) {
            if ( !is_object($obj) ) {
                $obj = new $obj;
            }

            $methods = $obj->register_method( );

            foreach ($methods as $method => $callback) {
                $this->mixins[$method] = $callback;
            }
        }
    }
}
