<?php  // vim: ts=4 sts=4 sw=4


// SkinnySQL based on DBIx::Skinny 0.04
class SkinnySQL
{
    private $select             = array( );  // -- Array
    private $distinct           = false;     // -- Bool
    private $select_map         = array( );  // -- Hash
    private $select_map_reverse = array( );  // -- Hash
    private $from               = array( );  // -- Array
    private $joins              = array( );  // -- Array
    private $where              = array( );  // -- Array
    private $bind               = array( );  // -- Array
    private $group              = array( );  // -- Array
    private $order              = array( );  // -- Array
    private $having             = array( );  // -- Array
    private $where_values       = array( );  // -- Hash
    private $index_hint         = array( );  // -- Hash
    private $column_mutator     = null;      // -- ???
    private $limit              = null;      // -- Int
    private $offset             = null;      // -- Int
    private $comment            = null;      // -- Str
    private $skinny             = null;      // -- Object


    function __construct ($args=array( ))
    {
        foreach ($args as $key => $value) {
            $this->$key($value);
        }
    }


    function __call ($name, $args)
    {
        if ( !array_key_exists($name, get_object_vars($this)) ) {
            trigger_error("Cannot access property: $name", E_USER_ERROR);
        }

        if ( empty($args) ) {
            return $this->$name;
        }

        $this->$name = $args[0];
    }


    function add_select ($term, $col=null)
    {
        if ( is_array($term) ) {
            list($term, $col) = each($term);
        }

        if ( empty($col) && $col != 0 ) {
            $col = $term;
        }

        array_push($this->select, $term);

        $this->select_map[$term] = $col;
        $this->select_map_reverse[$col] = $term;

        return $this;
    }


    function add_join ($args)
    {
        list($table, $joins) = each($args);

        $push = array(
            'table' => $table,
            'joins' => $joins,
        );

        array_push($this->joins, $push);

        return $this;
    }


    // -- TODO: MySQL
    function add_index_hint ($args)
    {
        list($table, $hint) = each($args);

        $this->index_hint[$table] = array(
            'type' => empty($hint[type]) ? $hint[type] : 'USE',
            'list' => $table,
        );

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
            $sql .= join( ', ', array_map($func, $this->select) );
            $sql .= "\n";
        }

        $sql .= 'FROM ';

        if ( !empty($this->joins) ) {
            $initial_table_written = 0;

            foreach ($this->joins as $j) {
                $table = $j['table'];
                $joins = $j['joins'];

                // TODO: _add_index_hint
                // $table = $this->add_index_hint($table);

                if ( !$initial_table_written++ ) {
                    $sql .= $table;
                }

                foreach ($joins as $join) {
                    $sql .= ' '
                         .strtoupper($join['type'])
                         .' JOIN '
                         .$join['table']
                         .' ON '
                         .$join['condition'];
                }
            }

            if ( !empty($this->from) ) {
                $sql .= ', ';
            }
        }

        if ( !empty($this->from) ) {
            // TODO: _add_index_hint
            $sql .= is_array($this->from)
                  ? join(', ', $this->from)
                  : $this->from;
        }

        $sql .= "\n";
        $sql .= $this->as_sql_where( );

        $sql .= $this->as_aggregate('group');
        $sql .= $this->as_sql_having( );
        $sql .= $this->as_aggregate('order');

        $sql .= $this->as_limit( );

        if ( !empty($this->comment) ) {
            if ( preg_match('/([ 0-9a-zA-Z.:;()_#&,]+)/', $this->comment, $m) ) {
                $sql .= '-- '.$m[1];
            }
        }

        return $sql;
    }


    function as_limit ( )
    {
        if ( !is_integer($this->limit) || $this->limit == 0 ) {
            return '';
        }

        $n = $this->limit;

        return sprintf("LIMIT %d%s\n", $n,
            is_integer($this->offset) ? ' OFFSET '.intval($this->offset) : '');
    }


    function as_aggregate ($set)
    {
        if ( !($attribute = $this->$set) ) {
            return '';
        }

        if ( !is_array($attribute) || sizeof($attribute) == 0 ) {
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
        return is_array($this->where) && sizeof($this->where) != 0
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
        list($col,  $val) = each($args);
        list($term, $bind, $tcol) = $this->mk_term($col, $val);

        array_push($this->where, '('.$term.')');
        $this->bind = array_merge_recursive($this->bind, $bind);

        $this->where_values[$tcol] = $val;

        return $this;
    }


    function add_complex_where ($terms)
    {
        list($where, $bind) = $this->parse_array_terms($terms);

        array_push($this->where, $where);
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

        array_push($this->having, "($term)");
        $this->bind = array_merge_recursive($this->bind, $bind);

        return $this;
    }


    private function set_alias ($args)
    {
        if ( array_key_exists($args, $this->select_map) && !empty($this->select_map[$args]) ) {
            $alias = $this->select_map[$args];

            if ( !preg_match("/(?:^|\.)\Q$alias\E$/", $args) ) {
                $args = $args.' AS '.$alias;
            }
        }

        return $args;
    }


    private function set_attribute ($args)
    {
        return $args['column'].($args['desc'] ? (' '.$args['desc']) : '');
    }


    // TODO: column_mutator ???
    private function mk_term ($col, $val)
    {
        $term = '';
        $bind = array( );

        if ($this->ref($val) == 'ARRAY') {
            if ($this->ref($val[0]) != 'SCALAR' || $val[0] == '-and') {
                $logic  = 'OR';
                $values = $val;

                if ($val[0] == '-and') {
                    $logic = 'AND';
                    array_shift($values);
                }

                $terms = array( );

                foreach ($values as $v) {
                    list($t, $b) = $this->mk_term($col, $v);

                    array_push($terms, "($t)");

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

                $term = "$c $op (? AND ?)";
                $bind = $v;
            }
            else if ($op == 'inject') {
                $term = $col.' '.$val[$op];
            }
            else {
                $op = strtoupper($op);

                $term = "$c $op ?";
                array_push($bind, $v);
            }
        }
        else {
            $term = "$col = ?";
            array_push($bind, $val);
        }

        return array($term, $bind, $col);
    }


    private function parse_array_terms ($terms_list)
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

                    array_push($out2, $term);
                    array_push($bind, $bind2);
                }

                $out1 .= '('.join(' AND ', $out2).')';
            }
            else if ($this->ref($t) == 'ARRAY') {
                list($where, $bind2) = $this->parse_array_terms($t);
                array_push($bind, $bind2);
                $out1 = '('.$where.')';
            }

            array_push($out, ($out ? ' '.$logic.' ' : '').$out1);
        }

        return array(join('', $out), $bind);
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
}
