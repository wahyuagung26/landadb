<?php

namespace Cahkampung;

/**
 * Library untuk mempermudah eksekusi query ke database MySQL menggunakan PHP PDO
 *
 * @author Wahyu Agung <wahyuagung26@gmail.com>
 *
 * Versi 1.3.1
 */
class Landadb extends \PDO
{
    /**
     * String untuk menyimpan nama tabel
     * @var $table
     */
    private $table;

    /**
     * String  untuk menyimpan nama kolom yang akan di select
     * @var $columns
     */
    private $columns;

    /**
     * String untuk menyimpan parameter ketika select data
     * @var $whereClause
     */
    private $whereClause;

    /**
     * Array untuk menyimpn value parameter
     * @var $bindParam
     */
    private $bindParam;

    /**
     * String untuk menyimpan relasi ke tabel lain
     * @var $joinTable
     */
    private $joinTable;

    /**
     * Integer untuk menyimpan limit query
     * @var $limit
     */
    private $limit;

    /**
     * Integer untuk menyimpan offset query
     * @var $offset
     */
    private $offset;

    /**
     * String untuk menyimpan urutan waktu mengeksekusi query
     * @var $orderBy
     */
    private $orderBy;

    /**
     * String untuk menyimpan parameter query Group By
     * @var $groupBy
     */
    private $groupBy;

    /**
     * @var $grouped
     */
    private $grouped;

    /**
     * @var  $having
     */
    private $having;

    /**
     * Landa DB constructor
     */
    public function __construct($dbSetting)
    {
        $this->dbSetting = $dbSetting;
        $arr = array();
        $driver = "mysql";

        if (isset($this->dbSetting['DISPLAY_ERRORS']) && $this->dbSetting['DISPLAY_ERRORS'] == 'true') {
            $arr = array(
                \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
            );
        }

        if (isset($this->dbSetting['DB_DRIVER']) && !empty($this->dbSetting['DB_DRIVER'])) {
            $driver = $this->dbSetting['DB_DRIVER'];
        }

        @parent::__construct($driver . ":host=" . $this->dbSetting['DB_HOST'] . ";dbname=" . $this->dbSetting['DB_NAME'], $this->dbSetting['DB_USER'], $this->dbSetting['DB_PASS'], $arr);
    }

    /**
     * multiInsert memasukkan banyak data dengan 1 query
     * @param  string $tabel nama tabel
     * @param  array $data data yang akan diinput
     */
    public function multiInsert($tabel, $data)
    {
        /**
         * Prepare data
         */
        foreach ($data as $k => $v) {
            $columName = $dataInsert = [];
            foreach ($v as $key => $val) {
                $columName[]  = $key;
                $dataInsert[] = $this->escape($val);
            }
            $columValue[] = "(" . implode(",", $dataInsert) . ")";
        }

        /**
         * Insert data
         */
        if ((isset($columName) && !empty($columName)) && (isset($columValue) && !empty($columName))) {
            $this->run("INSERT INTO " . $tabel . " (" . implode(",", $columName) . ") VALUES " . implode(",", $columValue) . ";");
        }
    }

    /**
     * Auto generate created user id and created date
     *
     * @author Wahyu Agung
     *
     * @return array
     */
    public function created()
    {
        $created = array();
        if (isset($this->dbSetting['CREATED_USER'])) {
            $created[$this->dbSetting['CREATED_USER']] = isset($this->dbSetting['USER_ID']) ? $this->dbSetting['USER_ID'] : 0;
        }
        if (isset($this->dbSetting['CREATED_TIME'])) {
            $created[$this->dbSetting['CREATED_TIME']] = ($this->dbSetting['CREATED_TIME'] !== null && $this->dbSetting['CREATED_TIME'] == "date") ? date("Y-m-d H:i:s") : strtotime(date("Y-m-d H:i:s"));
        }
        return $created;
    }

    /**
     * Auto generate created user id and created date
     *
     * @author Wahyu Agung
     *
     * @return array
     */
    public function modified()
    {
        $created = array();
        if (isset($this->dbSetting['MODIFIED_USER']) != null) {
            $created[$this->dbSetting['MODIFIED_USER']] = isset($this->dbSetting['USER_ID']) ? $this->dbSetting['USER_ID'] : 0;
        }
        if (isset($this->dbSetting['MODIFIED_TIME']) != null) {
            $created[$this->dbSetting['MODIFIED_TIME']] = ($this->dbSetting['MODIFIED_TYPE'] !== null && $this->dbSetting['MODIFIED_TYPE'] == "date") ? date("Y-m-d H:i:s") : strtotime(date("Y-m-d H:i:s"));
        }
        return $created;
    }

    /**
     * Memberi tanda string (quote) kepada nilai yang digunakan untuk parameter
     *
     * @author Wahyu Agung
     *
     * @param  string $data
     * @return string
     */
    public function escape($data)
    {
        return $this->quote(trim($data));
    }

    /**
     * Membersihkan query yang telah tergenerate
     *
     * @author Wahyu Agung
     *
     * @return object
     */
    public function clearQuery()
    {
        $this->columns      = '';
        $this->joinTable   = '';
        $this->bindParam   = [];
        $this->limit        = 0;
        $this->offset       = 0;
        $this->orderBy      = '';
        $this->groupBy      = '';
        $this->whereClause = '';
        $this->table        = '';
        $this->having       = '';
    }

    /**
     * Menjalankan query yang telah tergenerate
     *
     * @author Wahyu Agung
     *
     * @param  string $query
     * @param  array  $bind
     *
     * @return object
     */
    public function run($query, $bind = array())
    {
        $query = trim($query);
        try {
            $result = $this->prepare($query);
            $result->execute($bind);

            return $result;
        } catch (\Exception $e) {
            try {
                if (function_exists('logError')) {
                    $query = $this->sqlDebug($query, $bind);
                    logError($e, $query);
                } else {
                    echo $e->getMessage() . '<br>';
                    echo $query;
                }
            } catch (\Exception $re) {
                if (function_exists('logError')) {
                    $query = $this->sqlDebug($query, $bind);
                    logError($e, $query);
                } else {
                    echo $e->getMessage() . '<br>';
                    echo $query;
                }
            }
        }
    }

    /**
     * Ambil semua nama kolom pada tabel dan seleksi key dari
     * array yang akan diproses harus sama dengan nama kolom
     *
     * @author Wahyu Agung
     *
     * @param  string $table
     * @param  array $data
     * @return array
     */
    public function fieldFilter($table, $data)
    {
        $stmt         = $this->query("DESCRIBE $table");
        $list         = $stmt->fetchAll($this::FETCH_OBJ);
        $tableField = array();
        foreach ($list as $val) {
            $tableField[] = $val->Field;
        }
        return array_values(array_intersect($tableField, array_keys($data)));
    }

    /**
     * Clean
     *
     * @author Wahyu Agung
     *
     * @param  string $string
     * @return string
     */
    public function clean($string)
    {
        $string = str_replace(' ', '-', $string);
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    }

    /**
     * Ambil kolom primary key dari sebuah tabel
     *
     * @author Wahyu Agung
     *
     * @param string $table
     * @return void
     */
    public function getPrimary($table)
    {
        $field = $this->find("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
        return (isset($field->Column_name)) ? $field->Column_name : '';
    }

    /**
     * Ambil IP pengguna untuk user log
     *
     * @author Wahyu Agung
     *
     * @return string
     */
    public function getClientIp()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }

    /**
     * Aktifkan log ke dalam file
     *
     * @author Wahyu Agung
     *
     * @param  string $message keterangan untuk aktifitas yang direkam
     * @param  array $data
     */
    public function userlog($message, $data)
    {
        if (isset($this->dbSetting['USER_LOG']) && !empty($this->dbSetting['USER_LOG']) && $this->dbSetting['USER_LOG'] == true) {
            $logfolder = isset($this->dbSetting['LOG_FOLDER']) ? $this->dbSetting['LOG_FOLDER'] : 'userlog';
            $folder    = $logfolder . '/' . date("m-Y");

            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $userId   = isset($this->dbSetting['USER_ID']) ? $this->dbSetting['USER_ID'] : 0;
            $userNama = isset($this->dbSetting['USER_NAMA']) ? $this->dbSetting['USER_NAMA'] : 0;
            $msg      = date("d-m-Y H:i:s") . " (" . $this->getClientIp() . ") : $userNama (id : $userId) $message | $data";

            // Simpan aktifitas pada file log
            file_put_contents($folder . '/' . date("d-m-Y") . '.log', $msg . "\n", FILE_APPEND);
        }
    }

    /**
     * Generate Query untuk input data ke tabel
     *
     * @author Wahyu Agung
     *
     * @param  string $table
     * @param  array $data
     *
     * @return object data yang baru saja diinput
     */
    public function insert($table, $data, $autoLog = true, $msg = "")
    {
        $bind = [];

        // Set created_at dan modified_at otomatis
        $data = array_merge($this->created(), $data);
        $data = array_merge($this->modified(), $data);

        // Seleksi array yang masuk key nya harus sesuai dengan nama kolom
        $fields = $this->fieldFilter($table, $data);

        // Generate query insert
        $sql    = "INSERT INTO " . $table . " (" . implode(", ", $fields) . ") VALUES (:" . implode(", :", $fields) . ");";
        foreach ($fields as $field) {
            $bind[":$field"] = $data[$field];
        }
        $this->run($sql, $bind);

        // Ambil data yang baru saja diinput
        $lastId = $this->lastInsertId();
        $pk = $this->getPrimary($table);
        $data = $this->find("SELECT * FROM $table WHERE $pk = $lastId");

        //Jika parameter log aktif maka rekam aktifitas ke file log
        if ($autoLog) {
            if (empty($msg)) {
                $msg = "menginput ke tabel $table";
            }

            $this->userlog($msg, json_encode($data));
        }

        return $data;
    }

    /**
     * Generate Query untuk update data
     *
     * @author Wahyu Agung
     *
     * @param  string $table
     * @param  array $data
     * @param  array  $where
     *
     * @return object
     */
    public function update($table, $data, $where, $autoLog = true, $msg = "")
    {
        $bind          = [];
        $data          = array_merge($data, $this->modified());
        $created       = array_keys($this->created());
        $created_field = isset($created[1]) ? $created[1] : '';

        if (isset($data[$created_field])) {
            unset($data[$created_field]);
        }

        if (empty($data)) {
            return [];
        }

        // Setting data yang akan diupdate
        $fields = $this->fieldFilter($table, $data);
        foreach ($fields as $key => $val) {
            $set[]                = "$val = :update_" . $val;
            $bind[":update_$val"] = $data[$val];
        }

        // Setting parameter, jika parameter $where adalah array maka looping dan jadikan string
        if (is_array($where)) {
            $param = '';
            foreach ($where as $k => $vals) {
                if (empty($param)) {
                    $param .= " WHERE $k = :where_$k";
                } else {
                    $param .= " and $k =  :where_$k";
                }
                $bind[":where_$k"] = $vals;
            }
        } else {
            $param = ' WHERE ' . $where;
        }

        // Generate query untuk update data
        $sql = "UPDATE " . $table . " SET " . implode(', ', $set) . " $param ";
        $this->run($sql, $bind);

        // Ambil data yang baru saja diupdate
        $pk = $this->getPrimary($table);
        if (isset($data['id'])) {
            $data = $this->find("SELECT * FROM $table WHERE $pk = '" . $data['id'] . "'");
        } else {
            if (is_array($where)) {
                $this->select("*")->from($table);
                foreach ($where as $k => $vals) {
                    $this->andWhere($k, '=', $vals);
                }
                $data = $this->find();
            } else {
                $data = $this->find("SELECT * FROM $table $param");
            }
        }

        //Jika parameter log aktif maka rekam aktifitas ke file log
        if ($autoLog) {
            if (empty($msg)) {
                $msg = "mengubah tabel $table";
            }
            $this->userlog($msg, json_encode($data));
        }

        return $data;
    }

    /**
     * Generate query untuk hapus data
     *
     * @author Wahyu Agung
     *
     * @param  string $table
     * @param  array  $where
     *
     * @return object
     */
    public function delete($table, $where, $autoLog = true, $msg = "")
    {
        // Set parameter jika array, maka looping terlebih dahulu dan jadikan string
        $bind = [];
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

        // Generate query untuk menghapus data
        $sql = "DELETE FROM " . $table . " $param ";

        //Jika parameter log aktif maka rekam aktifitas ke file log
        if ($autoLog) {
            if (empty($msg)) {
                $msg = "menghapus data pada tabel $table";
            }
            $this->userlog($msg, json_encode($where));
        }

        return $this->run($sql, $bind);
    }

    /**
     * Menentukan data apa saja yang akan diambil dari sebuah query
     *
     * @author Wahyu Agung
     *
     * @param  string $columns
     *
     * @return object
     */
    public function select($columns = '')
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
     * Menentukan tabel yang akan diambil datanya
     *
     * @author Wahyu Agung
     *
     * @param  string $table
     * @return object
     */
    public function FROM($table)
    {
        if (is_array($table)) {
            $this->table = implode(",", $table);
        } else {
            $this->table = $table;
        }

        $this->table = trim($table);
        return $this;
    }

    /**
     * Merelasikan tabel utama dengan tabel lainnya
     *
     * @author Wahyu Agung
     *
     * @param  string $joinType
     * @param  string $table
     * @param  string $clause
     *
     * @return object
     */
    public function join($joinType, $table, $clause)
    {
        // Generate query join
        $this->joinTable .= " $joinType " . $table . " ON " . $clause;
        return $this;
    }

    /**
     * Membuat query innerJoin
     *
     * @author Wahyu Agung
     *
     * @param  string $table
     * @param  string $clause
     *
     * @return object
     */
    public function innerJoin($table, $clause)
    {
        $this->join('INNER JOIN', $table, $clause);
        return $this;
    }

    /**
     * Membuat query leftJoin
     *
     * @author Wahyu Agung
     *
     * @param  string $table
     * @param  string $clause
     *
     * @return object
     */
    public function leftJoin($table, $clause)
    {
        $this->join('LEFT JOIN', $table, $clause);
        return $this;
    }

    /**
     * Membuat query rightJoin
     *
     * @author Wahyu Agung
     *
     * @param  string $table
     * @param  string $clause
     *
     * @return object
     */
    public function rightJoin($table, $clause)
    {
        $this->join('RIGHT JOIN', $table, $clause);
        return $this;
    }

    /**
     * Membuat query customWhere
     *
     * @author Wahyu Agung
     *
     * @param  array $where
     *
     * @return object
     */
    public function customWhere($where, $param = "AND")
    {
        if (empty($param)) {
            $this->whereClause .= " (" . $where . ")";
        } else {
            if (empty($this->whereClause)) {
                $param = '';
            } else {
                $param = $param;
            }
            $this->whereClause .= " " . $param . " (" . $where . ")";
        }
        return $this;
    }

    /**
     * Membuat query where
     *
     * @author Wahyu Agung
     *
     * @param  string $filter
     * @param  string $column
     * @param  string $value
     * @param  string $nParam
     *
     * @return object
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

        $where = $this->whereClause;
        $jml = is_array($this->bindParam) ? count($this->bindParam) : 0;
        $i   = $jml + 1;

        if (empty($this->whereClause)) {
            $where = trim($column) . " $filter :where_" . $this->clean($column) . $i;
        } else {
            $where .= " $nParam " . trim($column) . " $filter :where_" . $this->clean($column) . $i;
        }

        $this->bindParam[":where_" . $this->clean($column) . $i] = $value;
        if ($this->grouped) {
            $this->whereClause .= '(' . $where;
            $this->grouped = false;
        } else {
            $this->whereClause = $where;
        }

        return $this;
    }

    /**
     * Membuat query andWhere
     *
     * @author Wahyu Agung
     *
     * @param  string $filter
     * @param  string $column
     * @param  string $value
     *
     * @return object
     */
    public function andWhere($column, $filter, $value)
    {
        $this->where($column, $filter, $value, 'AND');
        return $this;
    }

    /**
     * Membuat query orWhere
     *
     * @author Wahyu Agung
     *
     * @param  string $filter
     * @param  string $column
     * @param  string $value
     *
     * @return object
     */
    public function orWhere($column, $filter, $value)
    {
        $this->where($column, $filter, $value, 'OR');
        return $this;
    }

    /**
     * Membuat query limit
     *
     * @author Wahyu Agung
     *
     * @param  int $limit
     *
     * @return object
     */
    public function limit($limit = 0)
    {
        $this->limit = trim($limit);
        return $this;
    }

    /**
     * Membuat query offset
     *
     * @author Wahyu Agung
     *
     * @param  int $offset
     *
     * @return object
     */
    public function offset($offset = 0)
    {
        $this->offset = trim($offset);
        return $this;
    }

    /**
     * Membuat query order
     *
     * @author Wahyu Agung
     *
     * @param  string $order
     *
     * @return object
     */
    public function orderBy($order)
    {
        $this->orderBy = trim($order);
        return $this;
    }

    /**
     * Membuat query groupBy
     *
     * @author Wahyu Agung
     *
     * @param  string $group
     *
     * @return object
     */
    public function groupBy($group)
    {
        $this->groupBy = trim($group);
        return $this;
    }

    /**
     * Membuat query having
     *
     * @author Wahyu Agung
     *
     * @param string $param
     *
     *  @return object
     */
    public function having($param)
    {
        $this->having = trim($param);
        return $this;
    }

    /**
     * begin transactions
     */
    public function startTransaction()
    {
        return $this->beginTransaction();
    }

    /**
     * end transactions
     */
    public function endTransaction()
    {
        return $this->commit();
    }

    /**
     * rollback transactions
     */
    public function rollBackQuery()
    {
        return $this->rollBack();
    }

    /**
     * Membuat query count
     *
     * @author Wahyu Agung
     *
     * @return int
     */
    public function count()
    {
        $sql = 'SELECT COUNT(*) as jumlah FROM ' . $this->table . ' ' . $this->joinTable;
        if (!is_null($this->whereClause) && !empty($this->whereClause)) {
            $sql .= ' WHERE ' . $this->whereClause;
        }

        if (!is_null($this->groupBy) && !empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . $this->groupBy;
        }

        $exec = $this->run(trim($sql), $this->bindParam);

        $jumlah = 0;
        if (is_null($this->groupBy) || empty($this->groupBy)) {
            $count  = $exec->fetch($this::FETCH_OBJ);
            $jumlah = isset($count->jumlah) ? $count->jumlah : 0;
        } else {
            $model  = $exec->fetchAll($this::FETCH_OBJ);
            $jumlah = count($model);
        }

        return $jumlah;
    }

    /**
     * Membuat query grouped
     *
     * @author Wahyu Agung
     *
     * @param  array $obj
     *
     * @return object
     */
    public function grouped($obj)
    {
        $this->grouped = true;
        call_user_func($obj);
        $this->whereClause .= ')';
        return $this;
    }

    /**
     * prepareQuery
     *
     * @author Wahyu Agung
     *
     * @return array
     */
    public function prepareQuery()
    {
        $query = 'SELECT ' . $this->columns . ' FROM ' . $this->table;
        if (!is_null($this->joinTable) && !empty($this->joinTable)) {
            $query .= $this->joinTable;
        }
        if (!is_null($this->whereClause) && !empty($this->whereClause)) {
            $query .= ' WHERE ' . $this->whereClause;
        }
        if (!is_null($this->groupBy) && !empty($this->groupBy)) {
            $query .= ' GROUP BY ' . $this->groupBy;
        }
        if (!is_null($this->having) && !empty($this->having)) {
            $query .= ' HAVING ' . $this->having;
        }
        if (!is_null($this->orderBy) && !empty($this->orderBy)) {
            $query .= ' ORDER BY ' . $this->orderBy;
        }
        if (!is_null($this->limit) && !empty($this->limit)) {
            $query .= ' LIMIT ' . $this->limit;
        }
        if (!is_null($this->offset) && !empty($this->offset)) {
            $query .= ' OFFSET ' . $this->offset;
        }

        return array('query' => $query, 'bind' => $this->bindParam);
    }

    /**
     * Mengambil data dari sebuah tabel dan memberikan output berupa 1 data
     *
     * @author Wahyu Agung
     *
     * @param  string $sql
     *
     *  @return object
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
     * Mengambil data dari sebuah tabel dan memberikan output lebih dari 1 data
     *
     * @author Wahyu Agung
     *
     * @param  string $sql
     *
     * @return object
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

        try {
            return $exec->fetchAll($this::FETCH_OBJ);
        } catch (\Exception $e) {
            try {
                if (function_exists('logError')) {
                    $query = $this->sqlDebug($query['query'], $query['bind']);
                    logError($e, $query);
                } else {
                    echo $e->getMessage() . '<br>';
                    echo $query;
                }
            } catch (\Exception $re) {
                if (function_exists('logError')) {
                    $query = $this->sqlDebug($query['query'], $query['bind']);
                    logError($e, $query);
                } else {
                    echo $e->getMessage() . '<br>';
                    echo $query;
                }
            }
        }
    }

    /**
     * sqlDebug
     *
     * @author Wahyu Agung
     *
     * @param  string $sql_string
     * @param  array $params
     *
     * @return string
     */
    public function sqlDebug($sql_string, $params = [])
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
     * Menampilkan query dalam bentuk string
     *
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

        $string = '';
        if ($return === false) {
            $string .= $this->sqlDebug($query['query'], $query['bind']);
        } else {
            $string .= $this->sqlDebug($query['query'], $query['bind']);
        }

        echo $string;
    }
}
