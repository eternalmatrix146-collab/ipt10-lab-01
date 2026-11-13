<?php
// classes/EnrollmentRepository.php
// Data-access class (Repository pattern) that records students, enrollments
// and slot updates inside a single database transaction.
//
// Rule messages (for example "No slots available.") are thrown as
// RuntimeException so the page can show them to the administrator.
class EnrollmentRepository {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // The JOIN used by every enrollment list.
    private $detailsSql =
        "SELECT e.enrollment_id, e.enrollment_date, e.status,
                s.student_id, s.full_name, s.email,
                c.class_id, c.class_code, c.schedule, c.instructor,
                co.course_code, co.course_name
           FROM enrollments e
           JOIN students s ON e.student_id = s.student_id
           JOIN classes  c ON e.class_id   = c.class_id
           JOIN courses co ON c.course_id  = co.course_id";

    // Record a NEW student AND enroll them into a class.
    // Returns the new student_id.
    public function recordStudent($full_name, $email, $phone, $class_id) {
        try {
            // 1. Start the transaction.
            $this->db->beginTransaction();

            // 2. Check that the class still has available slots.
            $this->requireSlot($class_id);

            // 3. Insert the student.
            $stmt = $this->db->prepare(
                "INSERT INTO students (full_name, email, phone)
                 VALUES (:full_name, :email, :phone)");
            $stmt->execute([
                ':full_name' => $full_name, ':email' => $email, ':phone' => $phone
            ]);

            // 4. Get the new student's id.
            $student_id = $this->db->lastInsertId();

            // 5. Insert the enrollment.
            $stmt = $this->db->prepare(
                "INSERT INTO enrollments (student_id, class_id)
                 VALUES (:student_id, :class_id)");
            $stmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);

            // 6. Take one slot from the class.
            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id");
            $stmt->execute([':class_id' => $class_id]);

            // 7. Everything worked: save all three changes together.
            $this->db->commit();
            return $student_id;
        } catch (Exception $e) {
            // Something failed: undo every change made in this transaction.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Enroll an EXISTING student into a class.
    public function enroll($student_id, $class_id) {
        try {
            $this->db->beginTransaction();

            // The student must exist.
            $stmt = $this->db->prepare(
                "SELECT student_id FROM students WHERE student_id = :id");
            $stmt->execute([':id' => $student_id]);
            if (!$stmt->fetch()) {
                throw new RuntimeException("Student not found.");
            }

            // 1. Check the class still has available slots.
            $this->requireSlot($class_id);

            // No duplicates: only one ACTIVE enrollment per student per class.
            $stmt = $this->db->prepare(
                "SELECT enrollment_id FROM enrollments
                  WHERE student_id = :student_id AND class_id = :class_id
                    AND status = 'active'");
            $stmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);
            if ($stmt->fetch()) {
                throw new RuntimeException("This student is already enrolled in that class.");
            }

            // 2. Insert the enrollment.
            $stmt = $this->db->prepare(
                "INSERT INTO enrollments (student_id, class_id)
                 VALUES (:student_id, :class_id)");
            $stmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);

            // 3. Take one slot from the class.
            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id");
            $stmt->execute([':class_id' => $class_id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Cancel an enrollment and give the slot back to the class.
    public function cancel($enrollment_id) {
        try {
            $this->db->beginTransaction();

            // Find the enrollment. FOR UPDATE locks the row until we finish.
            $stmt = $this->db->prepare(
                "SELECT class_id, status FROM enrollments
                  WHERE enrollment_id = :id FOR UPDATE");
            $stmt->execute([':id' => $enrollment_id]);
            $enrollment = $stmt->fetch();
            if (!$enrollment) {
                throw new RuntimeException("Enrollment not found.");
            }
            if ($enrollment['status'] === 'cancelled') {
                throw new RuntimeException("This enrollment is already cancelled.");
            }

            // 1. Mark the enrollment as cancelled.
            $stmt = $this->db->prepare(
                "UPDATE enrollments SET status = 'cancelled' WHERE enrollment_id = :id");
            $stmt->execute([':id' => $enrollment_id]);

            // 2. Give the slot back to the class.
            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots + 1 WHERE class_id = :class_id");
            $stmt->execute([':class_id' => $enrollment['class_id']]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // All enrollments joined with student, class and course details.
    public function allWithDetails() {
        $stmt = $this->db->prepare(
            $this->detailsSql . " ORDER BY e.enrollment_id DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // One page of enrollments, filtered by keyword and/or status.
    // $keyword matches the student name, class code or course name.
    // $status is 'active', 'cancelled' or '' for all.
    public function search($keyword, $status, $limit, $offset) {
        list($where, $params) = $this->buildFilter($keyword, $status);
        $stmt = $this->db->prepare(
            $this->detailsSql . $where
            . " ORDER BY e.enrollment_id DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit',  (int) $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // How many enrollments match the same filter (needed for page numbers).
    public function countSearch($keyword, $status) {
        list($where, $params) = $this->buildFilter($keyword, $status);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total
               FROM enrollments e
               JOIN students s ON e.student_id = s.student_id
               JOIN classes  c ON e.class_id   = c.class_id
               JOIN courses co ON c.course_id  = co.course_id" . $where);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }

    // Report: every class with its number of active and cancelled enrollments.
    // LEFT JOIN keeps classes that have no enrollments yet.
    public function summaryByClass() {
        $stmt = $this->db->prepare(
            "SELECT co.course_name, c.class_code, c.schedule, c.instructor, c.slots,
                    COALESCE(SUM(e.status = 'active'), 0)    AS active_count,
                    COALESCE(SUM(e.status = 'cancelled'), 0) AS cancelled_count
               FROM classes c
               JOIN courses co ON c.course_id = co.course_id
               LEFT JOIN enrollments e ON e.class_id = c.class_id
              GROUP BY c.class_id, co.course_name, c.class_code,
                       c.schedule, c.instructor, c.slots
              ORDER BY co.course_name, c.class_code");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Stop with a message when the class is missing or full.
    // FOR UPDATE locks the class row so two enrollments cannot take the last slot.
    private function requireSlot($class_id) {
        $stmt = $this->db->prepare(
            "SELECT slots FROM classes WHERE class_id = :class_id FOR UPDATE");
        $stmt->execute([':class_id' => $class_id]);
        $class = $stmt->fetch();
        if (!$class) {
            throw new RuntimeException("Class not found.");
        }
        if ($class['slots'] <= 0) {
            throw new RuntimeException("No slots available.");
        }
    }

    // Build the WHERE part and its values for search() and countSearch().
    private function buildFilter($keyword, $status) {
        $cond   = [];
        $params = [];
        if ($keyword !== '') {
            // Escape % and _ so they are searched as normal characters.
            $like = '%' . addcslashes($keyword, '\\%_') . '%';
            $cond[] = "(s.full_name LIKE :kw1 OR c.class_code LIKE :kw2
                        OR co.course_name LIKE :kw3)";
            $params[':kw1'] = $like;
            $params[':kw2'] = $like;
            $params[':kw3'] = $like;
        }
        if ($status !== '') {
            $cond[] = "e.status = :status";
            $params[':status'] = $status;
        }
        $where = $cond ? " WHERE " . implode(" AND ", $cond) : "";
        return [$where, $params];
    }
}
