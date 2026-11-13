<?php
// admin/students.php -- Student recording form (multi-table)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Student.php';

$repo     = new EnrollmentRepository($db);
$classes  = new ClassSection($db);
$students = new Student($db);
$errors   = [];

$full_name = '';
$email     = '';
$phone     = '';
$class_id  = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $class_id  = (int) ($_POST['class_id'] ?? 0);

    // Server-side validation
    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    } elseif (strlen($full_name) > 100) {
        $errors[] = 'Full name must be 100 characters or fewer.';
    }
    if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+() -]{7,30}$/', $phone)) {
        $errors[] = 'Phone may only contain numbers, spaces and + ( ) - (7 to 30 characters).';
    }
    if ($class_id <= 0 || !$classes->find($class_id)) {
        $errors[] = 'Please choose a class.';
    }

    if (!$errors) {
        try {
            // One call = one transaction:
            // INSERT student + INSERT enrollment + slots - 1.
            $student_id = $repo->recordStudent($full_name, $email, $phone, $class_id);
            flash_and_redirect(
                "Student recorded and enrolled. (Student ID $student_id)", 'students.php');
        } catch (PDOException $e) {
            $errors[] = 'A database error happened. Nothing was saved.';
        } catch (RuntimeException $e) {
            // For example "No slots available." The transaction was rolled back.
            $errors[] = $e->getMessage() . ' Nothing was saved.';
        }
    }
}

$classList   = $classes->allWithCourse();   // for the class dropdown
$studentList = $students->all();
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Record Student</h1>
<p>Records a new student and enrolls them into a class in one step.</p>

<?php foreach ($errors as $error): ?>
    <p class="msg error"><?php echo e($error); ?></p>
<?php endforeach; ?>

<form method="post" action="students.php">
    <label>Full Name
        <input type="text" name="full_name" maxlength="100" required
               value="<?php echo e($full_name); ?>">
    </label>
    <label>Email
        <input type="email" name="email" maxlength="100"
               value="<?php echo e($email); ?>">
    </label>
    <label>Phone
        <input type="text" name="phone" maxlength="30"
               value="<?php echo e($phone); ?>">
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
    <button type="submit">Record Student</button>
</form>

<h2>Recorded Students</h2>
<table>
    <tr><th>ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Date Recorded</th></tr>
    <?php foreach ($studentList as $row): ?>
    <tr>
        <td><?php echo e($row['student_id']); ?></td>
        <td><?php echo e($row['full_name']); ?></td>
        <td><?php echo e($row['email']); ?></td>
        <td><?php echo e($row['phone']); ?></td>
        <td><?php echo e($row['created_at']); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$studentList): ?>
    <tr><td colspan="5">No students yet.</td></tr>
    <?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
