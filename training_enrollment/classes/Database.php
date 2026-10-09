<?php
// classes/Database.php
// PDO wrapper implementing the Singleton pattern.
class Database extends PDO {
    private static $instance = null;

    private function __construct($dsn, $user, $pass) {
        parent::__construct($dsn, $user, $pass);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function getInstance($dsn, $user = null, $pass = null) {
        if (self::$instance === null) {
            self::$instance = new Database($dsn, $user, $pass);
        }
        return self::$instance;
    }

    public function insert($table, array $data) {
        $cols = implode(", ", array_keys($data));
        $phs  = ":" . implode(", :", array_keys($data));
        $sql  = "INSERT INTO $table ($cols) VALUES ($phs)";
        $stmt = $this->prepare($sql);
        $stmt->execute($data);
        return $this->lastInsertId();
    }

    // Update rows. $where is a list of column => value pairs that must all match.
    // Example: $db->update('courses', ['course_name' => 'New'], ['course_id' => 1]);
    public function update($table, array $data, array $where) {
        $set    = [];
        $cond   = [];
        $params = [];
        foreach ($data as $col => $value) {
            $set[] = "$col = :set_$col";
            $params["set_$col"] = $value;
        }
        foreach ($where as $col => $value) {
            $cond[] = "$col = :where_$col";
            $params["where_$col"] = $value;
        }
        $sql  = "UPDATE $table SET " . implode(", ", $set)
              . " WHERE " . implode(" AND ", $cond);
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // Delete rows. $where is a list of column => value pairs that must all match.
    // Example: $db->delete('courses', ['course_id' => 1]);
    public function delete($table, array $where) {
        $cond = [];
        foreach ($where as $col => $value) {
            $cond[] = "$col = :$col";
        }
        $sql  = "DELETE FROM $table WHERE " . implode(" AND ", $cond);
        $stmt = $this->prepare($sql);
        $stmt->execute($where);
        return $stmt->rowCount();
    }

    public function getRows($sql, array $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRow($sql, array $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
