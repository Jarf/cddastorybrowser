<?php

class db {

    private $dbhost = DB_HOST;
    private $dbuser = DB_USER;
    private $dbpass = DB_PASS;
    private $dbname = DB_NAME;
    private $dbtype = DB_TYPE;
    private $conn;
    private $stmt;
    private $error;

    public function __construct(string $host = null, string $user = null, string $pass = null, string $name = null, string $type = null) {
        $args = array('host', 'user', 'pass', 'name', 'type');
        foreach($args as $arg){
            if($$arg !== null){
                $argname = 'db' . $arg;
                $this->$argname = $$arg;
            }
        }
        switch (strtolower($this->dbtype)) {
            case 'mysql':
                $dsn = "mysql:host={$this->dbhost};dbname={$this->dbname}";
                break;

            default:
                $this->error = 'Invalid DB Type';
                return;
        }
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        try {
            $this->conn = new PDO($dsn, $this->dbuser, $this->dbpass, $options);
        } catch (PDOException $ex) {
            die(print_r($ex->getMessage(), 1));
            $this->error = $ex->getMessage();
        }
    }

    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function fetchAll($fetchType = PDO::FETCH_OBJ) {
        return $this->stmt->fetchAll($fetchType);
    }

    public function fetch($fetchType = PDO::FETCH_OBJ) {
        return $this->stmt->fetch($fetchType);
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function endTransaction() {
        return $this->conn->commit();
    }

    public function cancelTransaction() {
        return $this->conn->rollBack();
    }

    public function debugParams() {
        $this->stmt->debugDumpParams();
    }

}
