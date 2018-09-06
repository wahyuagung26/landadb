<?php
namespace Cahkampung;

/**
 * Mysql PDO Library
 * author : Wahyu Agung Tribawono
 * email : wahyuagun26@gmail.com
 * versi : 1.2
 */

class Landadb extends \PDO
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
        $this->db_setting = $db_setting;

        if (isset($this->db_setting['DISPLAY_ERRORS']) && $this->db_setting['DISPLAY_ERRORS'] == 'true') {
            $arr = array(
                \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
            );
        } else {
            $arr = array();
        }

        if (isset($this->db_setting['DB_DRIVER']) && !empty($this->db_setting['DB_DRIVER'])) {
            $driver = $this->db_setting['DB_DRIVER'];
        } else {
            $driver = "mysql";
        }

        @parent::__construct($driver . ":host=" . $this->db_setting['DB_HOST'] . ";dbname=" . $this->db_setting['DB_NAME'], $this->db_setting['DB_USER'], $this->db_setting['DB_PASS'], $arr);
    }

    /**
     * auto generate created user id and created date
     * @return array
     */
    public function created()
    {
        $created = array();
        if (isset($this->db_setting['CREATED_USER'])) {
            $created[$this->db_setting['CREATED_USER']] = isset($this->db_setting['USER_ID']) ? $this->db_setting['USER_ID'] : 0;
        }

        if (isset($this->db_setting['CREATED_TIME'])) {
            $created[$this->db_setting['CREATED_TIME']] = ($this->db_setting['CREATED_TIME'] !== null && $this->db_setting['CREATED_TIME'] == "date") ? date("Y-m-d H:i:s") : strtotime(date("Y-m-d H:i:s"));
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
        if (isset($this->db_setting['MODIFIED_USER']) != null) {
            $created[$this->db_setting['MODIFIED_USER']] = isset($this->db_setting['USER_ID']) ? $this->db_setting['USER_ID'] : 0;
        }

        if (isset($this->db_setting['MODIFIED_TIME']) != null) {
            $created[$this->db_setting['MODIFIED_TIME']] = ($this->db_setting['MODIFIED_TYPE'] !== null && $this->db_setting['MODIFIED_TYPE'] == "date") ? date("Y-m-d H:i:s") : strtotime(date("Y-m-d H:i:s"));
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
        return $this->quote(trim($data));
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
        $this->groupBy      = null;
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
            $result = $this->prepare($query);
            $result->execute($bind);

            return $result;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

    }

    /**
     * get_field
     * @param  string $table
     * @param  array $data
     * @return array
     */
    public function get_field($table)
    {
        $stmt         = $this->query("DESCRIBE $table");
        $list         = $stmt->fetchAll($this::FETCH_OBJ);
        $table_fields = array();
        foreach ($list as $val) {
            $table_fields[] = $val->Field;
        }

        return $table_fields;
    }

    /**
     * field_filter
     * @param  string $table
     * @param  array $data
     * @return array
     */
    public function field_filter($table, $data)
    {
        $stmt         = $this->query("DESCRIBE $table");
        $list         = $stmt->fetchAll($this::FETCH_OBJ);
        $table_fields = array();
        foreach ($list as $val) {
            $table_fields[] = $val->Field;
        }

        return array_values(array_intersect($table_fields, array_keys($data)));
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
     * get primary key
     */
    public function getPrimary($table)
    {
        $field = $this->find("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
        return (isset($field->Column_name)) ? $field->Column_name : '';
    }

    /**
     * Get Ip
     */
    public function get_client_ip()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }

    /**
     * userLog
     * @param  [string] $message [description]
     * @param  [json] $id      [description]
     */
    public function userlog($message, $id)
    {
        if (isset($this->db_setting['USER_LOG']) && !empty($this->db_setting['USER_LOG']) && $this->db_setting['USER_LOG'] == true) {
            $logfolder = isset($this->db_setting['LOG_FOLDER']) ? $this->db_setting['LOG_FOLDER'] : 'userlog';
            $folder    = $logfolder . '/' . date("m-Y");
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $userId   = isset($this->db_setting['USER_ID']) ? $this->db_setting['USER_ID'] : 0;
            $userNama = isset($this->db_setting['USER_NAMA']) ? $this->db_setting['USER_NAMA'] : 0;
            $msg      = date("d-m-Y H:i:s")." (".$this->get_client_ip().") : $userNama (id : $userId) $message $id";

            file_put_contents($folder . '/' . date("d-m-Y") . '.log', $msg . "\n", FILE_APPEND);
        }
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

        $this->run($sql, $bind);
        $lastId = $this->lastInsertId();

        $pk = $this->getPrimary($table);

        /**
         * Log
         */
        $this->userlog("menginput tabel $table", json_encode(['id' => $lastId]));

        return $this->find("select * from $table where $pk = $lastId");
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
                    $param .= " and $k =  :where_$k";
                }

                $bind[":where_$k"] = $vals;
            }
        } else {
            $param = ' where ' . $where;
        }

        $sql = "UPDATE " . $table . " SET " . implode(', ', $set) . " $param ";
        $this->run($sql, $bind);

        $pk = $this->getPrimary($table);

        /**
         * Log
         */
        $this->userlog("mengupdate tabel $table", json_encode($where));

        if (isset($data['id'])) {
            return $this->find("select * from $table where $pk = '" . $data['id'] . "'");
        } else {
            if (is_array($where)) {
                $this->select("*")
                    ->from($table);

                foreach ($where as $k => $vals) {
                    $this->andWhere($k, '=', $vals);
                }
                return $this->find();
            } else {
                return $this->find("select * from $table $param");
            }
        }
    }

    /**
     * delete
     * @param  string $table
     * @param  array  $where
     * @return array
     */
    public function delete($table, $where)
    {
        /** Set param */
        if (is_array($where)) {
            $param = '';
            foreach ($where as $k => $vals) {
                if (empty($param)) {
                    $param .= " WHERE $k = :where_$k";
                } else {
                    $param .= " AND $k = :where_$k";
                }

                $bind[":where_$k"] = $vals;
            }
        } else {
            $param = $where;
        }

        $sql = "DELETE FROM " . $table . " $param ";

        /**
         * Log
         */
        $this->userlog("menghapus tabel $table", json_encode($where));

        return $this->run($sql, $bind);
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
        return $this;
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
        return $this;
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
        return $this;
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
    public function where($column, $filter, $value, $nParam = 'AND')
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

        $jml = is_array($this->bind_param) ? count($this->bind_param) : 0;
        $i   = $jml + 1;

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

        $exec  = $this->run(trim($sql), $this->bind_param);
        $count = $exec->fetch($this::FETCH_OBJ);
        return isset($count->jumlah) ? $count->jumlah : 0;
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

        $exec = $this->run(trim($query['query']), $query['bind']);
        return $exec->fetch($this::FETCH_OBJ);
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

        $exec = $this->run(trim($query['query']), $query['bind']);
        return $exec->fetchAll($this::FETCH_OBJ);
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
