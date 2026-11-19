<?php
// admin/classes.php -- Manage class schedules and slots (add, edit, delete)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$classes    = new ClassSection($db);
$courses    = new Course($db);
$courseList = $courses->all();   // for the course dropdown
$errors     = [];

// Values shown in the form (empty = "Add" mode).
$form = ['class_id' => 0, 'course_id' => 0, 'class_code' => '',
         'schedule' => '', 'instructor' => '', 'slots' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        try {
            $classes->delete((int) ($_POST['class_id'] ?? 0));
            flash_and_redirect('Class deleted.', 'classes.php');
        } catch (PDOException $e) {
            // Error 23000 = a database rule was broken. Here it is the foreign
            // key, which stops us from deleting a class that has enrollments.
            if ($e->getCode() != 23000) {
                throw $e;
            }
            $errors[] = 'Cannot delete this class because it has enrollment records.';
        }
    }

    if ($action === 'save') {
        $form['class_id']   = (int) ($_POST['class_id'] ?? 0);
        $form['course_id']  = (int) ($_POST['course_id'] ?? 0);
        $form['class_code'] = trim($_POST['class_code'] ?? '');
        $form['schedule']   = trim($_POST['schedule'] ?? '');
        $form['instructor'] = trim($_POST['instructor'] ?? '');
        $form['slots']      = trim($_POST['slots'] ?? '');

        // Server-side validation
        if (!$courses->find($form['course_id'])) {
            $errors[] = 'Please choose a course.';
        }
        if ($form['class_code'] === '') {
            $errors[] = 'Class code is required.';
        } elseif (strlen($form['class_code']) > 20) {
            $errors[] = 'Class code must be 20 characters or fewer.';
        }
        if (strlen($form['schedule']) > 100) {
            $errors[] = 'Schedule must be 100 characters or fewer.';
        }
        if (strlen($form['instructor']) > 100) {
            $errors[] = 'Instructor must be 100 characters or fewer.';
        }
        // ctype_digit is true only for whole numbers like "0", "5", "30".
        if (!ctype_digit($form['slots']) || (int) $form['slots'] > 1000) {
            $errors[] = 'Slots must be a whole number from 0 to 1000.';
        }

        if (!$errors) {
            if ($form['class_id'] > 0) {
                $classes->update($form['class_id'], $form['course_id'], $form['class_code'],
                                 $form['schedule'], $form['instructor'], (int) $form['slots']);
                flash_and_redirect('Class updated.', 'classes.php');
            } else {
                $classes->create($form['course_id'], $form['class_code'],
                                 $form['schedule'], $form['instructor'], (int) $form['slots']);
                flash_and_redirect('Class added.', 'classes.php');
            }
        }
    }
}

// "Edit" link clicked: load that class into the form.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $found = $classes->find((int) $_GET['edit']);
    if ($found) {
        $form = $found;
    }
}

$list = $classes->allWithCourse();   // JOIN: each class with its course name
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Classes</h1>

<?php foreach ($errors as $error): ?>
    <p class="msg error"><?php echo e($error); ?></p>
<?php endforeach; ?>

<h2><?php echo $form['class_id'] ? 'Edit Class' : 'Add Class'; ?></h2>
<?php if (!$courseList): ?>
    <p class="msg error">Add a course first before adding classes.</p>
<?php endif; ?>
<form method="post" action="classes.php">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="class_id" value="<?php echo e($form['class_id']); ?>">
    <label>Course
        <select name="course_id" required>
            <option value="">-- Choose a course --</option>
            <?php foreach ($courseList as $course): ?>
            <option value="<?php echo e($course['course_id']); ?>"
                <?php echo $course['course_id'] == $form['course_id'] ? 'selected' : ''; ?>>
                <?php echo e($course['course_code'] . ' - ' . $course['course_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Class Code
        <input type="text" name="class_code" maxlength="20" required
               value="<?php echo e($form['class_code']); ?>">
    </label>
    <label>Schedule
        <input type="text" name="schedule" maxlength="100"
               placeholder="e.g. Mon/Wed 9:00-11:00 AM"
               value="<?php echo e($form['schedule']); ?>">
    </label>
    <label>Instructor
        <input type="text" name="instructor" maxlength="100"
               value="<?php echo e($form['instructor']); ?>">
    </label>
    <label>Available Slots
        <input type="number" name="slots" min="0" max="1000" required
               value="<?php echo e($form['slots']); ?>">
    </label>
    <button type="submit"><?php echo $form['class_id'] ? 'Update Class' : 'Add Class'; ?></button>
    <?php if ($form['class_id']): ?>
        <a href="classes.php">Cancel edit</a>
    <?php endif; ?>
</form>

<h2>Class List</h2>
<table>
    <tr><th>ID</th><th>Course</th><th>Class Code</th><th>Schedule</th>
        <th>Instructor</th><th>Slots Left</th><th>Actions</th></tr>
    <?php foreach ($list as $row): ?>
    <tr>
        <td><?php echo e($row['class_id']); ?></td>
        <td><?php echo e($row['course_name']); ?></td>
        <td><?php echo e($row['class_code']); ?></td>
        <td><?php echo e($row['schedule']); ?></td>
        <td><?php echo e($row['instructor']); ?></td>
        <td>
            <?php echo e($row['slots']); ?>
            <?php if ($row['slots'] <= 0): ?><span class="badge cancelled">Full</span><?php endif; ?>
        </td>
        <td class="actions">
            <a href="classes.php?edit=<?php echo e($row['class_id']); ?>">Edit</a>
            <form method="post" action="classes.php" class="inline"
                  onsubmit="return confirm('Delete this class?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="class_id" value="<?php echo e($row['class_id']); ?>">
                <button type="submit" class="danger">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?>
    <tr><td colspan="7">No classes yet.</td></tr>
    <?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
