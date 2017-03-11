<?php
namespace Cahkampung\LandaDb;

class LandaDb
{
    /**
     * @var $db
     */
    private $db;

    /**
     * @var $table
     */
    private $table;

    /**
     * @var $columns
     */
    private $columns;

    /**
     * @var $where_clause
     */
    private $where_clause;

    /**
     * @var $bind_param
     */
    private $bind_param;

    /**
     * @var $join_table
     */
    private $join_table;

    /**
     * @var $limit
     */
    private $limit;

    /**
     * @var $offset
     */
    private $offset;

    /**
     * @var $orderBy
     */
    private $orderBy;

    /**
     * @var $groupBy
     */
    private $groupBy;

    /**
     * @var $grouped
     */
    private $grouped;

    /**
     * Landa DB constructor
     */
    public function __construct($db_setting)
    {
        $this->db = new PDO("mysql:host=" . $db_setting['DB_HOST'] . ";dbname=" . $db_setting['DB_NAME'], $db_setting['DB_USER'], $db_setting['DB_PASS']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->db;
    }

    /**
     * auto generate created user id and created date
     * @return array
     */
    public function created()
    {
        $created = array();
        if (isset($db_setting['CREATED_USER'])) {
            $created[$db_setting['CREATED_USER']] = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
        }

        if (isset($db_setting['CREATED_TIME'])) {
            $created[$db_setting['CREATED_TIME']] = ($db_setting['CREATED_TIME'] !== null && $db_setting['CREATED_TIME'] == "date") ? date("Y-m-d H:i:s") : strtotime(date("Y-m-d H:i:s"));
        }

        return $created;
    }

    /**
     * auto generate created user id and created date
     * @return array
     */
    public function modified()
    {
        $created = array();
        if (isset($db_setting['MODIFIED_USER']) != null) {
            $created[$db_setting['MODIFIED_USER']] = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
        }

        if (isset($db_setting['MODIFIED_TIME']) != null) {
            $created[$db_setting['MODIFIED_TIME']] = ($db_setting['MODIFIED_TYPE'] !== null && $db_setting['MODIFIED_TYPE'] == "date") ? date("Y-m-d H:i:s") : strtotime(date("Y-m-d H:i:s"));
        }

        return $created;
    }

    /**
     * escape
     * @param  string $data
     * @return string
     */
    public function escape($data)
    {
        return $this->db->quote(trim($data));
    }

    /**
     * clearQuery
     * @return object
     */
    public function clearQuery()
    {
        $this->columns      = null;
        $this->join_table   = null;
        $this->bind_param   = null;
        $this->limit        = null;
        $this->offset       = null;
        $this->orderBy      = null;
        $this->where_clause = null;
        $this->table        = null;
    }

    /**
     * run query
     * @param  string $query
     * @param  array  $bind
     * @return array
     */
    public function run($query, $bind = array())
    {
        $query = trim($query);
        try {
            $result = $this->db->prepare($query);
            $result->execute($bind);
            return $result;
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit(1);
        }
    }

    /**
     * field_filter
     * @param  string $table
     * @param  array $data
     * @return array
     */
    public function field_filter($table, $data)
    {
        $stmt         = $this->db->query("DESCRIBE $table");
        $list         = $stmt->fetchAll(PDO::FETCH_OBJ);
        $table_fields = array();
        foreach ($list as $val) {
            $table_fields[] = $val->Field;
        }
        return array_values(array_intersect($table_fields, array_keys($data)));
    }

    /**
     * get_data
     * @param  string $table
     * @param  int $id
     * @return array
     */
    public function get_data($table, $id)
    {
        $sql = "select * from $table where id = $id";
        try {
            $exec = $this->db->query($sql);
            $r    = $exec->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $r = $e->getMessage();
        }
        return $r;
    }

    /**
     * clean
     * @param  string $string
     * @return string
     */
    public function clean($string)
    {
        $string = str_replace(' ', '-', $string);
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    }

    /**
     * insert into database
     * @param  string $table
     * @param  array $data
     * @return array
     */
    public function insert($table, $data)
    {
        $bind = [];

        $data = array_merge($this->created(), $data);
        $data = array_merge($this->modified(), $data);

        $fields = $this->field_filter($table, $data);
        $sql    = "INSERT INTO " . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";

        foreach ($fields as $field) {
            $bind[":$field"] = $data[$field];
        }

        try {
            $this->run($sql, $bind);
            $lastId = $this->db->lastInsertId();
            $r      = $this->get_data($table, $lastId);
        } catch (Exception $e) {
            $r = $e->getMessage();
        }

        return $r;
    }

    /**
     * update data
     * @param  string $table
     * @param  array $data
     * @param  array  $where
     * @return array
     */
    public function update($table, $data, $where)
    {
        $bind = [];

        $data          = $this->modified() + $data;
        $created       = array_keys($this->created());
        $created_field = isset($created[1]) ? $created[1] : '';
        if (isset($data[$created_field])) {
            unset($data[$created_field]);
        }

        /** Set field value */
        $fields = $this->field_filter($table, $data);
        foreach ($fields as $key => $val) {
            $set[]                = "$val = :update_" . $val;
            $bind[":update_$val"] = $data[$val];
        }

        /** Set param */
        if (is_array($where)) {
            $param = '';
            foreach ($where as $k => $vals) {
                if (empty($param)) {
                    $param .= " where $k = :where_$k";
                } else {
                    $param .= " and $k =  :wher e_$k";
                }

                $bind[":where_$k"] = $vals;
            }
        } else {
            $param = $where;
        }

        try {
            $sql = "UPDATE " . $table . " SET " . implode(', ', $set) . " $param ";
            $this->run($sql, $bind);

            if (isset($data['id'])) {
                $r = $this->get_data($table, $data['id']);
            } else {
                $r = $data;
            }

        } catch (Exception $e) {
            $r = $e->getMessage();
        }

        return $r;
    }

    /**
     * delete
     * @param  string $table
     * @param  array  $where
     * @return array
     */
    public function delete($table, $where = array())
    {
        /** Set param */
        // if (is_array($where)) {
        $param = '';
        foreach ($where as $k => $vals) {
            if (empty($param)) {
                $param .= " WHERE $k = :where_$k";
            } else {
                $param .= " AND $k = :where_$k";
            }

            $bind[":where_$k"] = $vals;
        }
        // } else {
        //     $param = $where;
        // }

        $sql = "DELETE FROM " . $table . " $param ";

        try {
            $r = $this->run($sql, $bind);
        } catch (Exception $e) {
            $r = $e->getMessage();
        }

        return $r;
    }

    /**
     * select
     * @param  string $columns
     * @return array
     */
    public function select($columns = '*')
    {
        $this->clearQuery();

        if (is_array($columns)) {
            $this->columns = implode(",", $columns);
        } else {
            $this->columns = $columns;
        }

        return $this;
    }

    /**
     * from
     * @param  string $table
     * @return array
     */
    public function from($table)
    {
        if (is_array($table)) {
            $this->table = implode(",", $columns);
        } else {
            $this->table = $table;
        }

        $this->table = trim($table);
        return $this;
    }

    /**
     * join
     * @param  string $join_type
     * @param  string $table
     * @param  string $clause
     * @return array
     */
    public function join($join_type, $table, $clause)
    {
        $this->join_table .= " $join_type " . $table . " ON " . $clause;
        return $this;
    }

    /**
     * innerJoin
     * @param  string $table
     * @param  string $clause
     * @return array
     */
    public function innerJoin($table, $clause)
    {
        $this->join('INNER JOIN', $table, $clause);
    }

    /**
     * leftJoin
     * @param  string $table
     * @param  string $clause
     * @return array
     */
    public function leftJoin($table, $clause)
    {
        $this->join('LEFT JOIN', $table, $clause);
    }

    /**
     * rightJoin
     * @param  string $table
     * @param  string $clause
     * @return array
     */
    public function rightJoin($table, $clause)
    {
        $this->join('RIGHT JOIN', $table, $clause);
    }

    /**
     * customWhere
     * @param  array $where
     * @return [type]
     */
    public function customWhere($where, $param = '')
    {
        if (empty($param)) {
            $this->where_clause .= " (" . $where . ")";
        } else {
            if (empty($this->where_clause)) {
                $param = '';
            } else {
                $param = $param;
            }

            $this->where_clause .= " " . $param . " (" . $where . ")";
        }

        return $this;
    }

    /**
     * where
     * @param  string $filter
     * @param  string $column
     * @param  string $value
     * @param  string $nParam
     * @return array
     */
    public function where($filter, $column, $value, $nParam = 'AND')
    {
        if (is_array($value)) {
            $_keys = [];
            foreach ($value as $k => $v) {
                $_keys[] = (is_numeric($v) ? $v : $this->escape($v));
            }
            $value = "(" . implode(', ', $_keys) . ")";
        }

        if ($filter == "like" or $filter == "LIKE") {
            $value = '%' . $value . '%';
        } else {
            $value = $value;
        }

        $where = $this->where_clause;

        $i = count($this->bind_param) + 1;

        if (empty($this->where_clause)) {
            $where = trim($column) . " $filter :where_" . $this->clean($column) . $i;
        } else {
            $where .= " $nParam " . trim($column) . " $filter :where_" . $this->clean($column) . $i;
        }

        $this->bind_param[":where_" . $this->clean($column) . $i] = $value;

        if ($this->grouped) {
            $this->where_clause .= '(' . $where;
            $this->grouped = false;
        } else {
            $this->where_clause = $where;
        }

        return $this;
    }

    /**
     * andWhere
     * @param  string $filter
     * @param  string $column
     * @param  string $value
     * @return array
     */
    public function andWhere($filter, $column, $value)
    {
        $this->where($filter, $column, $value, 'AND');
        return $this;
    }

    /**
     * orWhere
     * @param  string $filter
     * @param  string $column
     * @param  string $value
     * @return array
     */
    public function orWhere($filter, $column, $value)
    {
        $this->where($filter, $column, $value, 'OR');
        return $this;
    }

    /**
     * limit
     * @param  int $limit
     * @return array
     */
    public function limit($limit = 0)
    {
        $this->limit = trim($limit);
        return $this;
    }

    /**
     * offset
     * @param  integer $offset
     * @return array
     */
    public function offset($offset = 0)
    {
        $this->offset = trim($offset);
        return $this;
    }

    /**
     * orderBy
     * @param  string $order
     * @return array
     */
    public function orderBy($order)
    {
        $this->orderBy = trim($order);
        return $this;
    }

    /**
     * groupBy
     * @param  string $group
     * @return array
     */
    public function groupBy($group)
    {
        $this->groupBy = trim($group);
        return $this;
    }

    /**
     * count
     * @return array
     */
    public function count()
    {
        $sql = 'SELECT COUNT(*) as jumlah FROM ' . $this->table . ' ' . $this->join_table;
        if (!is_null($this->where_clause)) {
            $sql .= ' WHERE ' . $this->where_clause;
        }

        try {
            $exec  = $this->run(trim($sql), $this->bind_param);
            $count = $exec->fetch(PDO::FETCH_OBJ);
            return $count->jumlah;
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    /**
     * grouped
     * @param  array $obj
     * @return array
     */
    public function grouped($obj)
    {
        $this->grouped = true;
        call_user_func($obj);
        $this->where_clause .= ')';
        return $this;
    }

    /**
     * prepareQuery
     * @return array
     */
    public function prepareQuery()
    {
        $query = 'SELECT ' . $this->columns . ' FROM ' . $this->table;

        if (!is_null($this->join_table)) {
            $query .= $this->join_table;
        }

        if (!is_null($this->where_clause)) {
            $query .= ' WHERE ' . $this->where_clause;
        }

        if (!is_null($this->groupBy)) {
            $query .= ' GROUP BY ' . $this->groupBy;
        }

        if (!is_null($this->orderBy)) {
            $query .= ' ORDER BY ' . $this->orderBy;
        }

        if (!is_null($this->limit)) {
            $query .= ' LIMIT ' . $this->limit;
        }

        if (!is_null($this->offset)) {
            $query .= ' OFFSET ' . $this->offset;
        }

        return array('query' => $query, 'bind' => $this->bind_param);
    }

    /**
     * find
     * @param  string $sql
     * @return array
     */
    public function find($sql = null)
    {
        if (empty($sql)) {
            $query = $this->prepareQuery();
        } else {
            $query['query'] = $sql;
            $query['bind']  = array();
        }

        try {
            $exec = $this->run(trim($query['query']), $query['bind']);
            return $exec->fetch(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    /**
     * findAll
     * @param  string $sql
     * @return array
     */
    public function findAll($sql = null)
    {
        if (empty($sql)) {
            $query = $this->prepareQuery();
        } else {
            $query['query'] = $sql;
            $query['bind']  = array();
        }

        try {
            $exec = $this->run(trim($query['query']), $query['bind']);
            return $exec->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    /**
     * sql_debug
     * @param  string     $sql_string
     * @param  array|null $params
     * @return string
     */
    public function sql_debug($sql_string, array $params = null)
    {
        if (!empty($params)) {
            $indexed = $params == array_values($params);
            foreach ($params as $k => $v) {
                if (is_object($v)) {
                    if ($v instanceof \DateTime) {
                        $v = $v->format('Y-m-d H:i:s');
                    } else {
                        continue;
                    }

                } elseif (is_string($v)) {
                    $v = "'$v'";
                } elseif ($v === null) {
                    $v = 'NULL';
                } elseif (is_array($v)) {
                    $v = implode(',', $v);
                }

                if ($indexed) {
                    $sql_string = preg_replace('/\?/', $v, $sql_string, 1);
                } else {
                    if ($k[0] != ':') {
                        $k = ':' . $k;
                    }
                    $sql_string = str_replace($k, $v, $sql_string);
                }
            }
        }
        return $sql_string;
    }

    /**
     * database log
     * @param  string $sql
     * @return string
     */
    public function log($sql = null, $return = false)
    {
        if (empty($sql)) {
            $query = $this->prepareQuery();
        } else {
            $query['query'] = $sql;
            $query['bind']  = array();
        }

        if ($return == false) {
            echo "<div class='well'>";
            echo $this->sql_debug($query['query'], $query['bind']);
            echo "</div>";
        } else {
            return $this->sql_debug($query['query'], $query['bind']);
        }
    }
}