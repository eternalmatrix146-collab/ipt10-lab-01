<?php
// admin/courses.php -- Manage courses (add, edit, delete)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Course.php';

$courses = new Course($db);
$errors  = [];

// Values shown in the form (empty = "Add" mode).
$form = ['course_id' => 0, 'course_code' => '', 'course_name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        try {
            $courses->delete((int) ($_POST['course_id'] ?? 0));
            flash_and_redirect('Course deleted.', 'courses.php');
        } catch (PDOException $e) {
            // Error 23000 = a database rule was broken. Here it is the foreign
            // key, which stops us from deleting a course that has classes.
            if ($e->getCode() != 23000) {
                throw $e;
            }
            $errors[] = 'Cannot delete this course because it still has classes.';
        }
    }

    if ($action === 'save') {
        $form['course_id']   = (int) ($_POST['course_id'] ?? 0);
        $form['course_code'] = trim($_POST['course_code'] ?? '');
        $form['course_name'] = trim($_POST['course_name'] ?? '');
        $form['description'] = trim($_POST['description'] ?? '');

        // Server-side validation
        if ($form['course_code'] === '') {
            $errors[] = 'Course code is required.';
        } elseif (strlen($form['course_code']) > 20) {
            $errors[] = 'Course code must be 20 characters or fewer.';
        }
        if ($form['course_name'] === '') {
            $errors[] = 'Course name is required.';
        } elseif (strlen($form['course_name']) > 100) {
            $errors[] = 'Course name must be 100 characters or fewer.';
        }

        if (!$errors) {
            try {
                if ($form['course_id'] > 0) {
                    $courses->update($form['course_id'], $form['course_code'],
                                     $form['course_name'], $form['description']);
                    flash_and_redirect('Course updated.', 'courses.php');
                } else {
                    $courses->create($form['course_code'], $form['course_name'],
                                     $form['description']);
                    flash_and_redirect('Course added.', 'courses.php');
                }
            } catch (PDOException $e) {
                // Error 23000 here = course_code is UNIQUE and this code is taken.
                if ($e->getCode() != 23000) {
                    throw $e;
                }
                $errors[] = 'That course code already exists.';
            }
        }
    }
}

// "Edit" link clicked: load that course into the form.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $found = $courses->find((int) $_GET['edit']);
    if ($found) {
        $form = $found;
    }
}

$list = $courses->all();
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Courses</h1>

<?php foreach ($errors as $error): ?>
    <p class="msg error"><?php echo e($error); ?></p>
<?php endforeach; ?>

<h2><?php echo $form['course_id'] ? 'Edit Course' : 'Add Course'; ?></h2>
<form method="post" action="courses.php">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="course_id" value="<?php echo e($form['course_id']); ?>">
    <label>Course Code
        <input type="text" name="course_code" maxlength="20" required
               value="<?php echo e($form['course_code']); ?>">
    </label>
    <label>Course Name
        <input type="text" name="course_name" maxlength="100" required
               value="<?php echo e($form['course_name']); ?>">
    </label>
    <label>Description
        <textarea name="description" rows="2"><?php echo e($form['description']); ?></textarea>
    </label>
    <button type="submit"><?php echo $form['course_id'] ? 'Update Course' : 'Add Course'; ?></button>
    <?php if ($form['course_id']): ?>
        <a href="courses.php">Cancel edit</a>
    <?php endif; ?>
</form>

<h2>Course List</h2>
<table>
    <tr><th>ID</th><th>Code</th><th>Name</th><th>Description</th><th>Actions</th></tr>
    <?php foreach ($list as $row): ?>
    <tr>
        <td><?php echo e($row['course_id']); ?></td>
        <td><?php echo e($row['course_code']); ?></td>
        <td><?php echo e($row['course_name']); ?></td>
        <td><?php echo e($row['description']); ?></td>
        <td class="actions">
            <a href="courses.php?edit=<?php echo e($row['course_id']); ?>">Edit</a>
            <form method="post" action="courses.php" class="inline"
                  onsubmit="return confirm('Delete this course?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="course_id" value="<?php echo e($row['course_id']); ?>">
                <button type="submit" class="danger">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?>
    <tr><td colspan="5">No courses yet.</td></tr>
    <?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
