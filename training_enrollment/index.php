<?php
// index.php -- Landing page
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
?>
<h1>Training Enrollment System</h1>
<p>Welcome, Administrator. Use this system to manage training courses and class
   schedules, record students, and track enrollments.</p>

<ul class="menu">
    <li><a href="admin/courses.php">Courses</a> &ndash; add, edit and delete courses</li>
    <li><a href="admin/classes.php">Classes</a> &ndash; manage class schedules and slots</li>
    <li><a href="admin/students.php">Record Student</a> &ndash; record a new student and enroll them</li>
    <li><a href="admin/enroll.php">Enroll</a> &ndash; enroll an existing student into a class</li>
    <li><a href="admin/enrollments.php">Enrollments</a> &ndash; view, search and cancel enrollments</li>
    <li><a href="admin/reports.php">Reports</a> &ndash; enrollment summary per class</li>
</ul>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
