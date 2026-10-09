<?php
// admin/enroll.php -- Enroll an EXISTING student into a class
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Student.php';

$repo     = new EnrollmentRepository($db);
$classes  = new ClassSection($db);
$students = new Student($db);
$errors   = [];

$student_id = 0;
$class_id   = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_id   = (int) ($_POST['class_id'] ?? 0);

    // Validate the ids
    if ($student_id <= 0 || !$students->find($student_id)) {
        $errors[] = 'Please choose a student.';
    }
    if ($class_id <= 0 || !$classes->find($class_id)) {
        $errors[] = 'Please choose a class.';
    }

    if (!$errors) {
        try {
            // One transaction: INSERT enrollment + slots - 1.
            $repo->enroll($student_id, $class_id);
            flash_and_redirect('Student enrolled.', 'enroll.php');
        } catch (PDOException $e) {
            $errors[] = 'A database error happened. Nothing was saved.';
        } catch (RuntimeException $e) {
            // "No slots available." or "already enrolled". Rolled back.
            $errors[] = $e->getMessage() . ' Nothing was saved.';
        }
    }
}

$studentList = $students->all();
$classList   = $classes->allWithCourse();
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Enroll Existing Student</h1>
<p>Enrolls a student who is already recorded into another class.</p>

<?php foreach ($errors as $error): ?>
    <p class="msg error"><?php echo e($error); ?></p>
<?php endforeach; ?>

<form method="post" action="enroll.php">
    <label>Student
        <select name="student_id" required>
            <option value="">-- Choose a student --</option>
            <?php foreach ($studentList as $student): ?>
            <option value="<?php echo e($student['student_id']); ?>"
                <?php echo $student['student_id'] == $student_id ? 'selected' : ''; ?>>
                <?php echo e($student['full_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Class
        <select name="class_id" required>
            <option value="">-- Choose a class --</option>
            <?php foreach ($classList as $class): ?>
            <option value="<?php echo e($class['class_id']); ?>"
                <?php echo $class['class_id'] == $class_id ? 'selected' : ''; ?>>
                <?php
                $slotText = $class['slots'] > 0 ? $class['slots'] . ' slots left' : 'FULL';
                echo e($class['class_code'] . ' - ' . $class['course_name'] . " ($slotText)");
                ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Enroll Student</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
