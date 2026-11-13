<?php
// admin/enrollments.php -- List enrollments (JOINs) with search, filter and paging
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo   = new EnrollmentRepository($db);
$errors = [];

// Search box, status filter and page number come from the address bar.
$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
if (!in_array($status, ['active', 'cancelled'], true)) {
    $status = '';                       // anything else means "all"
}
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 5;

// Build a link to this page that keeps the current search and filter.
function page_url($q, $status, $page) {
    return 'enrollments.php?' . http_build_query(
        ['q' => $q, 'status' => $status, 'page' => $page]);
}

// "Cancel" button clicked.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    try {
        // One transaction: status = 'cancelled' + slots + 1.
        $repo->cancel((int) ($_POST['enrollment_id'] ?? 0));
        flash_and_redirect('Enrollment cancelled. The slot was returned to the class.',
                           page_url($q, $status, $page));
    } catch (PDOException $e) {
        $errors[] = 'A database error happened. Nothing was changed.';
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }
}

// Fetch one page of enrollments with student, class and course details.
$total      = $repo->countSearch($q, $status);
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$rows       = $repo->search($q, $status, $perPage, ($page - 1) * $perPage);

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Enrollments</h1>

<?php foreach ($errors as $error): ?>
    <p class="msg error"><?php echo e($error); ?></p>
<?php endforeach; ?>

<form method="get" action="enrollments.php" class="filter">
    <input type="text" name="q" placeholder="Search student, class or course"
           value="<?php echo e($q); ?>">
    <select name="status">
        <option value="">All statuses</option>
        <option value="active"    <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
    </select>
    <button type="submit">Search</button>
    <a href="enrollments.php">Reset</a>
</form>

<p><?php echo e($total); ?> enrollment(s) found.</p>

<table>
    <tr><th>ID</th><th>Student</th><th>Course</th><th>Class</th><th>Schedule</th>
        <th>Instructor</th><th>Date Enrolled</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?php echo e($row['enrollment_id']); ?></td>
        <td><?php echo e($row['full_name']); ?></td>
        <td><?php echo e($row['course_name']); ?></td>
        <td><?php echo e($row['class_code']); ?></td>
        <td><?php echo e($row['schedule']); ?></td>
        <td><?php echo e($row['instructor']); ?></td>
        <td><?php echo e($row['enrollment_date']); ?></td>
        <td><span class="badge <?php echo e($row['status']); ?>"><?php echo e($row['status']); ?></span></td>
        <td>
            <?php if ($row['status'] === 'active'): ?>
            <form method="post" action="<?php echo e(page_url($q, $status, $page)); ?>" class="inline"
                  onsubmit="return confirm('Cancel this enrollment?');">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="enrollment_id" value="<?php echo e($row['enrollment_id']); ?>">
                <button type="submit" class="danger">Cancel</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
    <tr><td colspan="9">No enrollments found.</td></tr>
    <?php endif; ?>
</table>

<?php if ($totalPages > 1): ?>
<p class="pages">
    Page:
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <?php if ($p === $page): ?>
            <strong><?php echo $p; ?></strong>
        <?php else: ?>
            <a href="<?php echo e(page_url($q, $status, $p)); ?>"><?php echo $p; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</p>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
