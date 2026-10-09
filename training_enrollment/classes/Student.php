<?php
// classes/Student.php
// Reads student records.
// Note: $db is the single Database object (Database extends PDO).
class Student {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // Return one student by id, or false when not found.
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE student_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Return all students ordered by name.
    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM students ORDER BY full_name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
