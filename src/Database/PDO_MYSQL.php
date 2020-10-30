<?php

namespace Mooda\Database;

use PDO;
use PDOException;
use Exception;

Class PDO_MYSQL extends \Mooda {

    private $config = array();
    private $current_db_name = '';
    private $table_prefix = '';
    private $pdo_debug = array();
    private $pdo_host, $pdo_query = null;

    public function __construct($config = null) {
        if (!empty($config)) {
            $this->config = $this->set_config($config);
        }
    }

    private function set_config($cnf) {
        return array(
            'hostname' => isset($cnf['hostname']) ? strval($cnf['hostname']) : 'localhost',
            'port' => isset($cnf['port']) ? intval($cnf['port']) : 3306,
            'charset' => isset($cnf['charset']) ? strval($cnf['charset']) : 'utf8',
            'database' => isset($cnf['database']) ? strval($cnf['database']) : 'database',
            'username' => isset($cnf['username']) ? strval($cnf['username']) : 'root',
            'password' => isset($cnf['password']) ? strval($cnf['password']) : null,
            'table_prefix' => isset($cnf['table_prefix']) ? strval($cnf['table_prefix']) : null,
            'pdo_config' => isset($cnf['pdo_config']) ? $cnf['pdo_config'] : array(),
            'pdo_attribute' => isset($cnf['pdo_attribute']) ? $cnf['pdo_attribute'] : array()
        );
    }

    public function Connect($config = null) {
        if (empty($config)) {
            $config = $this->config;
        }

        $cnf = $this->set_config($config);

        $this->table_prefix = isset($cnf['table_prefix']) ? $cnf['table_prefix'] : '';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s;', $cnf['hostname'], $cnf['port'], $cnf['database'], $cnf['charset']);

        $pdo_attr = isset($cnf['pdo_attribute']) ? $cnf['pdo_attribute'] : array();

        if (!isset($pdo_attr[PDO::ATTR_DEFAULT_FETCH_MODE])) {
            $pdo_attr[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        }

        if (!isset($pdo_attr[PDO::ATTR_ERRMODE])) {
            $pdo_attr[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }

        try {
            $this->pdo_host = new PDO($dsn, $cnf['username'], $cnf['password'], $cnf['pdo_config']);

            foreach ($pdo_attr as $key => $value) {
                $this->pdo_host->setAttribute($key, $value);
            }

            $this->current_db_name = $cnf['database'];
        } catch (PDOException $ex) {
            $this->current_db_name = null;
            return parent::halt('Database Error', $ex->getMessage());
        }

        return $this->pdo_host;
    }

    public function Close() {
        $this->table_prefix = '';
        $this->pdo_debug = array();
        $this->pdo_host = null;
        $this->pdo_query = null;
        $this->current_db_name = null;
    }

    public function isConnect() {
        if (!$this->pdo_host) {
            return false;
        }

        return true;
    }

    public function CurrentDatabase() {
        if (!$this->isConnect()) {
            return false;
        }

        return $this->current_db_name;
    }

    public function Query($sql, $bind_data = null) {
        if (is_array($bind_data)) {
            return $this->Execute($sql, $bind_data);
        }

        $this->pdo_debug['sql_command'] = $sql;

        try {
            $this->pdo_query = $this->pdo_host->query($sql);
            return true;
        } catch (PDOException $ex) {
            $this->pdo_debug['error_message'] = 'PDOException: ' . $ex->getMessage();
        } catch (Exception $ex) {
            $this->pdo_debug['error_message'] = 'Exception: ' . $ex->getMessage();
        }

        return false;
    }

    public function Execute($sql, $bind_data = null) {
        if (!is_array($bind_data)) {
            return $this->Query($sql, $bind_data);
        }

        $this->pdo_debug['sql_command'] = $sql;

        try {
            $this->pdo_query = $this->pdo_host->prepare($sql);

            foreach ($bind_data as $key => $value) {
                if (strpos($sql, ':' . $key) !== false) {//valid parameter in SQL Command
                    $this->pdo_query->bindValue(':' . $key, $value, $this->BindType($value));
                }
            }

            return $this->pdo_query->execute();
        } catch (PDOException $ex) {
            $this->pdo_debug['error_message'] = 'PDOException: ' . $ex->getMessage();
        } catch (Exception $ex) {
            $this->pdo_debug['error_message'] = 'Exception: ' . $ex->getMessage();
        }

        return false;
    }

    public function lastInsertId() {
        if (!$this->pdo_host) {
            return false;
        }

        return $this->pdo_host->lastInsertId();
    }

    public function rowCount() {
        if (!$this->pdo_query) {
            return 0;
        }

        return $this->pdo_query->rowCount();
    }

    public function Debug($key = null) {
        if (!empty($key)) {
            return (empty($this->pdo_debug[$key]) ? null : $this->pdo_debug[$key]);
        }
        return $this->pdo_debug;
    }

    public function Select($options) {
        $table = isset($options['table']) ? $this->table_prefix . $options['table'] : $this->table_prefix . strval($options);
        $column = isset($options['column']) ? $options['column'] : '*';
        $join = isset($options['join']) ? $this->SelectJoin($options['join']) : null;
        $terms = $this->SelectCondition($options);
        $bind_data = isset($options['bind_data']) ? $options['bind_data'] : null;

        $sql = rtrim("SELECT $column FROM $table $join $terms", ' ') . ';';
        $result = $this->Query($sql, $bind_data);

        if (!$result) {
            return false;
        }

        $f_style = isset($options['fetch_style']) ? $options['fetch_style'] : 'ASSOC';
        $f_mode = isset($options['fetch_mode']) ? $options['fetch_mode'] : 'fetchAll';

        return $this->FetchData($f_mode, $f_style);
    }

    public function Insert($options) {
        $table = isset($options['table']) ? $this->table_prefix . $options['table'] : null;
        $data = isset($options['data']) ? $options['data'] : null;
        $data_set = $this->InsertDataset($data);

        $sql = rtrim("INSERT INTO $table $data_set", ' ') . ';';
        $result = $this->Execute($sql, $data);

        if (!$result) {
            return false;
        }

        $last_ins_id = isset($options['return_last_id']) ? $options['return_last_id'] : true;

        if ($last_ins_id === true) {
            return $this->lastInsertId();
        }

        return true;
    }

    public function Update($options) {
        $table = isset($options['table']) ? $this->table_prefix . $options['table'] : $options;
        $join = isset($options['join']) ? $this->SelectJoin($options['join']) : null;
        $data = isset($options['data']) ? $options['data'] : array();
        $bind_data = isset($options['bind_data']) ? $options['bind_data'] : array();
        $terms = $this->SelectCondition($options);

        if (is_array($data)) {
            $data_set = $this->UpdateDataset($data);
            $bind_data = array_merge($data, $bind_data);
        }

        $sql = rtrim("UPDATE $table $join SET $data_set $terms", ' ') . ';';
        $result = $this->Execute($sql, $bind_data);

        if (!$result) {
            return false;
        }

        $affected_row = isset($options['return_affected_rows']) ? $options['return_affected_rows'] : true;

        if ($affected_row === true) {
            return $this->rowCount();
        }

        return true;
    }

    public function Delete($options) {
        $table = isset($options['table']) ? $this->table_prefix . $options['table'] : $options;
        $join = isset($options['join']) ? $this->SelectJoin($options['join']) : null;
        $terms = $this->SelectCondition($options);
        $bind_data = isset($options['bind_data']) ? $options['bind_data'] : null;

        $sql = rtrim("DELETE FROM $table $join $terms", ' ') . ';';
        $result = $this->Execute($sql, $bind_data);

        if (!$result) {
            return false;
        }

        return $this->rowCount();
    }

    public function ShowColumn($table) {
        $table_name = isset($table) ? $this->table_prefix . $table : null;

        $keys_sql = sprintf('SELECT k.column_name FROM information_schema.table_constraints t JOIN information_schema.key_column_usage k USING(constraint_name,table_schema,table_name) WHERE t.constraint_type=\'PRIMARY KEY\' AND t.table_schema=\'%s\' AND t.table_name=\'%s\'', $this->current_db_name, $table_name);

        $this->Query($keys_sql);

        $keys_item = $this->FetchData('fetchColumn');

        $cols_sql = sprintf('SELECT column_name FROM information_schema.columns WHERE table_schema=\'%s\' and table_name=\'%s\'', $this->current_db_name, $table_name);

        $this->Query($cols_sql);

        $cols_get = $this->FetchData('fetchAll', 'COLUMN');

        return array('primary_key' => $keys_item, 'columns' => array_diff($cols_get, array("$keys_item")));
    }

    public function ShowTable() {
        $this->Query("SHOW TABLES");

        return $this->FetchData('fetchAll', 'COLUMN');
    }

    private function BindType($value) {
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }

        if (is_int($value) || is_integer($value)) {
            return PDO::PARAM_INT;
        }

        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    private function FetchData($mode, $style = null) {//allow with self::$mode
        if (!$this->pdo_query) {
            return null;
        }

        if (!in_array($mode, array('fetch', 'fetchAll', 'fetchColumn', 'fetchObject'))) {
            return null;
        }

        if ($mode === 'fetchColumn') {
            return $this->pdo_query->$mode(0);
        }

        $styles = empty($style) ? null : strtoupper($style);

        if ($styles === 'ASSOC') {
            return $this->pdo_query->$mode(PDO::FETCH_ASSOC);
        }

        if ($styles === 'BOTH') {
            return $this->pdo_query->$mode(PDO::FETCH_BOTH);
        }

        if ($styles === 'COLUMN') {
            return $this->pdo_query->$mode(PDO::FETCH_COLUMN);
        }

        if ($styles === 'NUM') {
            return $this->pdo_query->$mode(PDO::FETCH_NUM);
        }

        return $this->pdo_query->$mode();
    }

    private function InsertDataset($data) {
        if (!is_array($data)) {
            return "VALUES ($data)";
        }

        $column = array();
        $values = array();

        foreach ($data as $key => $val) {
            $column[] = $key;
            $values[] = sprintf(':%s', $key);
        }

        $data_column = implode(',', $column);
        $data_values = implode(',', $values);

        return "($data_column) VALUES ($data_values)";
    }

    private function UpdateDataset($data) {
        if (sizeof($data) < 1) {
            return null;
        }

        $data_set = null;

        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                $val = $this->CheckValue($val);
            }
            $data_set .= sprintf(",%s=:%s", $key, $key);
        }

        return ltrim($data_set, ',');
    }

    private function CheckValue($str) {
        if (empty($str)) {
            return $str;
        }

        if (is_array($str)) {
            return serialize($str);
        }

        $val1 = ltrim(rtrim($str, " "), " ");
        $val2 = htmlspecialchars(htmlspecialchars_decode($val1), ENT_QUOTES, 'UTF-8');

        return $val2;
    }

    private function SelectJoin($set_var) {
        if (!isset($set_var[0])) {
            return $set_var;
        }

        $join_term = null;

        if (is_array($set_var[0])) {//check join 2 array
            foreach ($set_var as $value) {
                $join_term .= ' ' . $this->SelectJoin($value);
            }
        } else {
            if (is_array($set_var)) {
                $join_term = sprintf("%s %s ON (%s) ", strtoupper($set_var[0]), $this->table_prefix . $set_var[1], $set_var[2]);
            } else {
                $join_term = $set_var;
            }
        }

        return $join_term;
    }

    private function SelectCondition($options) {
        $where = isset($options['where']) ? sprintf('WHERE (%s)', $options['where']) : null;
        $group_by = isset($options['group_by']) ? sprintf('GROUP BY %s', $options['group_by']) : null;
        $order_by = isset($options['order_by']) ? sprintf('ORDER BY %s', $options['order_by']) : null;
        $limit = isset($options['limit']) ? sprintf('LIMIT %s', $options['limit']) : null;

        $condition = array();

        if (!empty($options['where'])) {
            $condition[] = $where;
        }

        if (!empty($options['group_by'])) {
            $condition[] = $group_by;
        }

        if (!empty($options['order_by'])) {
            $condition[] = $order_by;
        }

        if (!empty($options['limit'])) {
            $condition[] = $limit;
        }

        return implode(' ', $condition);
    }

}
