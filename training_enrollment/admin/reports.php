<?php
// admin/reports.php -- Summary report: enrollments per class
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);
$rows = $repo->summaryByClass();

// Add up the columns for the "Total" row.
$totalActive    = 0;
$totalCancelled = 0;
$totalSlots     = 0;
foreach ($rows as $row) {
    $totalActive    += $row['active_count'];
    $totalCancelled += $row['cancelled_count'];
    $totalSlots     += $row['slots'];
}

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Reports</h1>
<h2>Enrollment Summary per Class</h2>

<table>
    <tr><th>Course</th><th>Class</th><th>Schedule</th><th>Instructor</th>
        <th>Active Enrollments</th><th>Cancelled Enrollments</th><th>Slots Left</th></tr>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?php echo e($row['course_name']); ?></td>
        <td><?php echo e($row['class_code']); ?></td>
        <td><?php echo e($row['schedule']); ?></td>
        <td><?php echo e($row['instructor']); ?></td>
        <td><?php echo e($row['active_count']); ?></td>
        <td><?php echo e($row['cancelled_count']); ?></td>
        <td><?php echo e($row['slots']); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
    <tr><td colspan="7">No classes yet.</td></tr>
    <?php else: ?>
    <tr class="total">
        <td colspan="4">Total</td>
        <td><?php echo e($totalActive); ?></td>
        <td><?php echo e($totalCancelled); ?></td>
        <td><?php echo e($totalSlots); ?></td>
    </tr>
    <?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
