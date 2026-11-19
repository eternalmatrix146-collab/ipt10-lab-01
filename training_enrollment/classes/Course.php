<?php
// classes/Course.php
// Course data access (CRUD).
// Note: $db is the single Database object, so its helper methods
// (insert, update, delete, getRow, getRows) can be used here.
class Course {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // Return all courses ordered by name.
    public function all() {
        return $this->db->getRows("SELECT * FROM courses ORDER BY course_name");
    }

    // Return one course by id, or false when not found.
    public function find($id) {
        return $this->db->getRow(
            "SELECT * FROM courses WHERE course_id = :id", [':id' => $id]);
    }

    // Insert a course and return its new id.
    public function create($code, $name, $description) {
        return $this->db->insert('courses', [
            'course_code' => $code,
            'course_name' => $name,
            'description' => $description,
        ]);
    }

    // Update a course.
    public function update($id, $code, $name, $description) {
        return $this->db->update('courses', [
            'course_code' => $code,
            'course_name' => $name,
            'description' => $description,
        ], ['course_id' => $id]);
    }

    // Delete a course. MySQL refuses this (foreign key) if the course has classes.
    public function delete($id) {
        return $this->db->delete('courses', ['course_id' => $id]);
    }
}
