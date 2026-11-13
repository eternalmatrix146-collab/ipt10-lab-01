<?php // includes/header.php
// $base comes from includes/auth.php: it is "../" for pages inside admin/
// and "" for pages in the main folder, so every link below works from both.

// Escape text before printing it in HTML (prevents XSS).
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Training Enrollment System</title>
    <link rel="stylesheet" href="<?php echo $base; ?>css/style.css">
</head>
<body>
<nav>
    <a href="<?php echo $base; ?>index.php">Home</a> |
    <a href="<?php echo $base; ?>admin/courses.php">Courses</a> |
    <a href="<?php echo $base; ?>admin/classes.php">Classes</a> |
    <a href="<?php echo $base; ?>admin/students.php">Record Student</a> |
    <a href="<?php echo $base; ?>admin/enroll.php">Enroll</a> |
    <a href="<?php echo $base; ?>admin/enrollments.php">Enrollments</a> |
    <a href="<?php echo $base; ?>admin/reports.php">Reports</a> |
    <a href="<?php echo $base; ?>logout.php">Log Out</a>
</nav>
<main>
<?php
// Show the one-time success message saved by flash_and_redirect().
if (!empty($_SESSION['flash'])) {
    echo '<p class="msg success">' . e($_SESSION['flash']) . '</p>';
    unset($_SESSION['flash']);
}
?>
