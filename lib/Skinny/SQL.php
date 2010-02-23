<?php


// SkinnySQL based on DBIx::Skinny 0.04
class SkinnySQL
{
    protected $select             = array( );  // -- Array
    protected $distinct           = false;     // -- Bool
    protected $select_map         = array( );  // -- Hash
    protected $select_map_reverse = array( );  // -- Hash
    protected $from               = array( );  // -- Array
    protected $joins              = array( );  // -- Array
    protected $where              = array( );  // -- Array
    protected $bind               = array( );  // -- Array
    protected $group              = array( );  // -- Array
    protected $order              = array( );  // -- Array
    protected $having             = array( );  // -- Array
    protected $where_values       = array( );  // -- Hash
    protected $index_hint         = array( );  // -- Hash
    protected $limit              = null;      // -- Int
    protected $offset             = null;      // -- Int
    protected $comment            = null;      // -- Str
    protected $skinny             = null;      // -- Object


    function __construct ($args=array( ))
    {
        foreach ($args as $key => $value) {
            $this->$key($value);
        }
    }


    function __call ($name, $args)
    {
        if ( !array_key_exists($name, get_object_vars($this)) ) {
            $trace = debug_backtrace( );
            $trace = $trace[1];
            var_dump( $trace );
            trigger_error("Cannot access property: $name", E_USER_ERROR);
        }

        if ( empty($args) ) {
            return $this->$name;
        }

        $this->$name = is_array($args[0]) ? $args[0] : $args;
    }


    function bind ( ) { return $this->bind; }


    protected function skinny ($skinny=null)
    {
        $this->skinny = $skinny;
    }


    function select ($select=null)
    {
        if ( is_null($select) ) {
            return $this->select;
        }

        $this->select = $select;
        return $this;
    }


    function add_select ($terms, $col=null)
    {
        switch ( $this->ref($terms) ) {
        case 'HASH':
            foreach ($terms as $term => $col) {
                if ( is_array($this->select) ) {
                    $this->select[ ] = $term;
                }
                else {
                    $this->select = array($this->select, $term);
                }
                $this->select_map[$term] = $col;
                $this->select_map_reverse[$col] = $term;
            }
            break;

        case 'ARRAY':
            foreach ($terms as $term) {
                $col = $term;

                if ( is_array($this->select) ) {
                    $this->select[ ] = $term;
                }
                else {
                    $this->select = array($this->select, $term);
                }
                $this->select_map[$term] = $col;
                $this->select_map_reverse[$col] = $term;
            }
            break;

        case 'SCALAR':
            if ( !$col ) {
                $col = $terms;
            }

            if ( is_array($this->select) ) {
                $this->select[ ] = $terms;
            }
            else {
                $this->select = array($this->select, $term);
            }
            $this->select_map[$terms] = $col;
            $this->select_map_reverse[$col] = $terms;

            break;

        default:
            trigger_error('Parse Error: add_select', E_USER_ERROR);
            return;
        }

        return $this;
    }


    function select_map ($map=null)
    {
        if ( is_null($map) ) {
            return $this->select_map;
        }

        $this->select_map = $map;
        return $this;
    }


    function distinct ($distinct=null)
    {
        if ( is_null($distinct) ) {
            return $this->distinct;
        }

        $this->distinct = $distinct;
        return $this;
    }


    function from ($args)
    {
        switch ( $this->ref($args) ) {
        case 'ARRAY':
            $this->from = $args;
            break;

        case 'HASH':
            foreach ($args as $term => $name) {
                $terms[ ] = $term.' '.$name;
            }

            $this->from = $terms;
            break;

        case 'SCALAR':
            $this->from = array($args);
            break;

        default:
            trigger_error('Parse Error: from', E_USER_ERROR);
        }

        return $this;
    }


    function add_join ($args)
    {
        list($table, $joins) = each($args);

        $this->joins[ ] = array(
            'table' => $table,
            'joins' => $joins,
        );

        return $this;
    }


    function add_index_hint ($args)
    {
        list($table, $hint) = each($args);

        $this->index_hint[$table] = array(
            'type' => empty($hint['type'])
                    ? $hint['type']
                    : 'USE',
            'list' => is_array($hint['list'])
                    ? $hint['list']
                    : array($hint['list']),
        );

        return $this;
    }


    function comment ($comment)
    {
        $this->comment = $comment;
        return $this;
    }


    function joins ($joins)
    {
        $this->joins = $joins;
        return $this;
    }


    function as_sql ( )
    {
        $sql = '';

        if ( $this->select ) {
            $sql .= 'SELECT ';

            if ($this->distinct) {
                $sql .= 'DISTINCT ';
            }

            $func = array($this, 'set_alias');

            if ( is_array($this->select) ) {
                $sql .= join( ', ', array_map($func, $this->select) );
            }
            else {
                $sql .= $this->select;
            }
            $sql .= "\n";
        }

        if ( !empty($this->joins) || !empty($this->from) ) {
            $sql .= 'FROM ';
        }

        if ( !empty($this->joins) ) {
            $initial_table_written = 0;

            if ($this->ref($this->joins) == 'HASH') {
                $this->joins = array($this->joins);
            }

            foreach ($this->joins as $j) {
                $table = $j['table'];
                $joins = $this->ref($j['joins']) == 'HASH'
                       ? array($j['joins'])
                       : $j['joins'];

                $table = $this->_add_index_hint($table);

                if ( !$initial_table_written++ ) {
                    $sql .= $table;
                }

                foreach ($joins as $join) {
                    if ($this->ref($join['condition']) == 'ARRAY') {
                        $mode = ' USING ';
                        $join['condition'] = join(', ', $join['condition']);
                    }
                    else {
                        $mode = ' ON ';
                    }

                    $sql .= ' '
                         .strtoupper($join['type'])
                         .' JOIN '
                         .$join['table']
                         .$mode
                         .'('.$join['condition'].')';
                }
            }

            if ( !empty($this->from) ) {
                $sql .= ', ';
            }
        }

        if ( !empty($this->from) ) {
            $sql .= join(', ', array_map(array($this, '_add_index_hint'), $this->from));
        }

        if ( !preg_match("/\n$/", $sql) ) {
            $sql .= "\n";
        }

        $sql .= $this->as_sql_where( );

        $sql .= $this->as_aggregate('group');
        $sql .= $this->as_sql_having( );
        $sql .= $this->as_aggregate('order');

        $sql .= $this->as_limit( );

        if ( !empty($this->comment) ) {
            $comment = is_array($this->comment)
                     ? $this->comment[0]
                     : $this->comment;

            if ( preg_match('/([ 0-9a-zA-Z.:;()_#&,]+)/', $comment, $m) ) {
                $sql .= '-- '.$m[1];
            }
        }

        return $sql;
    }


    function limit ($limit)
    {
        $this->limit = $limit;
        return $this;
    }


    function offset ($offset)
    {
        $this->offset = $offset;
        return $this;
    }


    function as_limit ( )
    {
        $limit  = is_array($this->limit)  ? $this->limit[0]  : $this->limit;
        $offset = is_array($this->offset) ? $this->offset[0] : $this->offset;

        if ( empty($limit) ) {
            return '';
        }

        if ( !is_integer($limit) ) {
            trigger_error(
                "Non-numerics in limit clause ($limit)", E_USER_ERROR
            );
        }

        return sprintf("LIMIT %d%s\n",
            $limit,
            is_integer($offset) ? ' OFFSET '.intval($offset) : ''
        );
    }


    function group ($group)
    {
        $this->group = $group;
        return $this;
    }


    function order ($order)
    {
        $this->order = $order;
        return $this;
    }


    function as_aggregate ($set)
    {
        if ( !($attribute = $this->$set) ) {
            return '';
        }

        if ( sizeof($attribute) == 0 ) {
            return '';
        }

        if ( $this->ref($attribute) == 'HASH') {
            $attribute = array($attribute);
        }

        $func = array($this, 'set_attribute');
        return strtoupper($set)
            .' BY '
            .join(', ', array_map($func, $attribute))
            ."\n";
    }


    function as_sql_where ( )
    {
        return sizeof($this->where) != 0
             ? 'WHERE '.join(' AND ', $this->where)."\n"
             : '';
    }


    function as_sql_having ( )
    {
        return empty($this->having)
             ? ''
             : 'HAVING '.join(' AND ', $this->having)."\n";
    }


    function add_where ($args)
    {
        if ( empty($args) ) {
            return $this;
        }

        if ($this->ref($args) == 'HASH') {
            foreach ($args as $col => $val) {
                list($term, $bind, $tcol) = $this->mk_term($col, $val);

                $this->where[ ] = '('.$term.')';
                $this->bind = array_merge_recursive($this->bind, $bind);

                $this->where_values[$tcol] = $val;
            }
        }
        else {
            trigger_error('argument must be hash', E_USER_ERROR);
        }

        return $this;
    }


    function add_complex_where ($terms)
    {
        list($where, $bind) = $this->parse_array_terms($terms);

        $this->where[ ] = $where;
        $this->bind = array_merge_recursive($this->bind,  $bind);

        return $this;
    }


    function has_where ($args)
    {
        list($col, $val) = each($args);

        return array_key_exists($this->where_values[$col]);
    }


    function add_having ($args)
    {
        list($col, $val) = each($args);

        if ($orig = $this->select_map_reverse[$col]) {
            $col = $orig;
        }

        list($term, $bind) = $this->mk_term($col, $val);

        $this->having[ ] = "($term)";
        $this->bind = array_merge_recursive($this->bind, $bind);

        return $this;
    }


    protected function _add_index_hint ($tbl_name)
    {
        $hint = @$this->index_hint[$tbl_name];

        if ( !($hint && $this->ref($hint) == 'HASH') ) {
            return $tbl_name;
        }

        if ( !empty($hint['list']) ) {
            $retval  = $tbl_name.' ';
            $retval .= $hint['type'] ? strtoupper($hint['type']) : 'USE';
            $retval .= ' INDEX ('.join(', ', $hint['list']).')';

            return $retval;
        }

        return $tbl_name;
    }


    protected function set_alias ($args)
    {
        if ( array_key_exists($args, $this->select_map) && !empty($this->select_map[$args]) ) {
            $alias = $this->select_map[$args];

            if ( !preg_match("/(?:^|\.)\Q$alias\E$/", $args) ) {
                $args = $args.' AS '.$alias;
            }
        }

        return $args;
    }


    protected function set_attribute ($args)
    {
        return $args['column'].(@$args['desc'] ? (' '.$args['desc']) : '');
    }


    protected function mk_term ($col, $val)
    {
        $term = '';
        $bind = array( );

        if ($this->ref($val) == 'ARRAY') {
            if ($this->ref($val[0]) !== 'SCALAR' || $val[0] === '-and') {
                $logic  = 'OR';
                $values = $val;

                if ($val[0] == '-and') {
                    $logic = 'AND';
                    array_shift($values);
                }

                $terms = array( );

                foreach ($values as $v) {
                    list($t, $b) = $this->mk_term($col, $v);

                    $terms[ ] = "($t)";

                    $bind = array_merge_recursive($bind, $b);
                }

                $term = join(" $logic ", $terms);
            }
            else {
                $term = "$col IN (".join(', ', array_fill(0, sizeof($val), '?')).')';
                $bind = $val;
            }
        }
        else if ($this->ref($val) == 'HASH') {
            $c = empty($val['column'])
               ? $col
               : $val['column'];
        
            list($op, $v) = each($val);

            if ( ($op == 'in' || $op == 'not in') && $this->ref($v) == 'ARRAY') {
                $op = strtoupper($op);

                $term = "$c $op (".join(', ', array_fill(0, sizeof($v), '?')).')';
                $bind = $v;
            }
            else if ( ($op == 'between' || $op == 'not between') && $this->ref($v) == 'ARRAY') {
                $op = strtoupper($op);

                $term = "$c $op ? AND ?";
                $bind = $v;
            }
            else if ($op == 'inject') {
                $term = $col.' '.$val[$op];
            }
            else if ( ($c == 'or' || $c == 'and') && $this->ref($val) == 'HASH') {
                $logic = strtoupper($c);
                $terms = array( );

                foreach ($val as $c2 => $v2) {
                    list($t, $b) = $this->mk_term($c2, $v2);
                    $terms[ ] = '('.$t.')';
                    $bind = array_merge_recursive($bind, $b);
                }

                $term = join(" $logic ", $terms);
            }
            else {
                $op = strtoupper($op);

                $term = "$c $op ?";
                $bind[ ] = $v;
            }
        }
        else {
            if ( is_null($val) ) {
                $term = "$col IS NULL";
            }
            else if ( is_bool($val) ) {
                $term  = "$col IS ".($val ? 'TRUE' : 'FALSE');
            }
            else {
                $term = "$col = ?";
                $bind[ ] = $val;
            }
        }

        return array($term, $bind, $col);
    }


    protected function parse_array_terms ($terms_list)
    {
        $out   = array( );
        $logic = 'AND';
        $bind  = array( );

        foreach ($terms_list as $t) {
            if ($this->ref($t) == 'SCALAR') {
                if ( preg_match('-?/OR|AND|OR_NOT|AND_NOT)$/', strtoupper($t), $m) ) {
                    $logic = $m[1];
                }

                $logic = strtr($logic, '_', ' ');

                continue;
            }

            $out1 = '';

            if ($this->ref($t) == 'HASH') {
                $out2 = array( );

                foreach (array_keys($t) as $t2) {
                    list($term, $bind2, $col) = $this->mk_term($t2, $t[$t2]);
                    $this->where_values[$col] = $t[$t2];

                    $out2[ ] = $term;
                    $bind[ ] = $bind2;
                }

                $out1 .= '('.join(' AND ', $out2).')';
            }
            else if ($this->ref($t) == 'ARRAY') {
                list($where, $bind2) = $this->parse_array_terms($t);
                $bind[ ] =  $bind2;
                $out1 = '('.$where.')';
            }

            $out[ ] = ($out ? ' '.$logic.' ' : '').$out1;
        }

        return array(join('', $out), $bind);
    }


    function retrieve ($table=null)
    {
        if ( !$table ) {
            $table = @$this->from[0];
        }

        return $this->skinny->search_by_sql(
            $this->as_sql( ), $this->bind, $table
        );
    }


    protected function ref ($val)
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
}
