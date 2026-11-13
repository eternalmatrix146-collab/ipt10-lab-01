<?php
// classes/ClassSection.php
// Class (schedule) data access. "slots" means the slots still available.
class ClassSection {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // JOIN classes with courses so each row also shows the course name.
    public function allWithCourse() {
        return $this->db->getRows(
            "SELECT c.*, co.course_code, co.course_name
               FROM classes c
               JOIN courses co ON c.course_id = co.course_id
              ORDER BY co.course_name, c.class_code");
    }

    // Return one class by id, or false when not found.
    public function find($id) {
        return $this->db->getRow(
            "SELECT * FROM classes WHERE class_id = :id", [':id' => $id]);
    }

    // Insert a class and return its new id.
    public function create($course_id, $code, $schedule, $instructor, $slots) {
        return $this->db->insert('classes', [
            'course_id'  => $course_id,
            'class_code' => $code,
            'schedule'   => $schedule,
            'instructor' => $instructor,
            'slots'      => $slots,
        ]);
    }

    // Update a class.
    public function update($id, $course_id, $code, $schedule, $instructor, $slots) {
        return $this->db->update('classes', [
            'course_id'  => $course_id,
            'class_code' => $code,
            'schedule'   => $schedule,
            'instructor' => $instructor,
            'slots'      => $slots,
        ], ['class_id' => $id]);
    }

    // Delete a class. MySQL refuses this (foreign key) if it has enrollments.
    public function delete($id) {
        return $this->db->delete('classes', ['class_id' => $id]);
    }

    // Return the remaining slots for a class (false when the class does not exist).
    public function getSlots($class_id) {
        $row = $this->db->getRow(
            "SELECT slots FROM classes WHERE class_id = :id", [':id' => $class_id]);
        return $row ? (int) $row['slots'] : false;
    }
}
