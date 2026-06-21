<?php

class Database {
    private $conn;
    private $stmt;
    private $bindVars = [];
    private $bindTypes = '';
    
    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("Database Connection Error: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    /**
     * Prepared statement bilan query bajarish
     */
    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
        $this->bindVars = [];
        $this->bindTypes = '';
        
        if (!$this->stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        
        return $this;
    }
    
    /**
     * Parameters bind qilish
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = 'i';
                    break;
                case is_float($value):
                    $type = 'd';
                    break;
                default:
                    $type = 's';
            }
        }
        
        $this->bindTypes .= $type;
        $this->bindVars[] = $value;
        return $this;
    }
    
    /**
     * Execute binding va query
     */
    private function executeBinding() {
        if (!empty($this->bindVars) && !empty($this->bindTypes)) {
            $this->stmt->bind_param($this->bindTypes, ...$this->bindVars);
        }
    }
    
    /**
     * Bitta satr qaytarish
     */
    public function single() {
        $this->executeBinding();
        $this->stmt->execute();
        $result = $this->stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Barcha sarflar qaytarish
     */
    public function resultSet() {
        $this->executeBinding();
        $this->stmt->execute();
        $result = $this->stmt->get_result();
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Satr soni qaytarish
     */
    public function rowCount() {
        return $this->stmt->affected_rows;
    }
    
    /**
     * Last insert ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    /**
     * Direct query (safe) - faqat SELECT uchun
     */
    public function directQuery($sql) {
        $result = $this->conn->query($sql);
        
        if (!$result) {
            throw new Exception("Query Error: " . $this->conn->error);
        }
        
        return $result;
    }
    
    /**
     * Transaction boshlash
     */
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    /**
     * Commit
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Rollback
     */
    public function rollback() {
        $this->conn->rollback();
    }
    
    /**
     * String escape (xavfsiz qilish)
     */
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    /**
     * Database yopish
     */
    public function closeConnection() {
        if ($this->stmt) {
            $this->stmt->close();
        }
        $this->conn->close();
    }
}
?>