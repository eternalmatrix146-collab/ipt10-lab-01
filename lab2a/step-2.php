<?php

require "helpers/helper-functions.php";

session_start();


if (!isset($_SESSION['fullname'])) {

    header("Location: step-1.php");
    exit();

}


if (
    empty($_POST['fullname']) &&
    isset($_POST['program'])
) {

}


if ($_SERVER["REQUEST_METHOD"] == "POST") {


if (
    empty($_POST['fullname']) ||
    empty($_POST['birthday']) ||
    empty($_POST['contact_number']) ||
    empty($_POST['sex'])
) {

header("Location: step-1.php");
exit();

}


$_SESSION['fullname'] = $_POST['fullname'];
$_SESSION['birthday'] = $_POST['birthday'];
$_SESSION['contact_number'] = $_POST['contact_number'];
$_SESSION['sex'] = $_POST['sex'];


}


?>

<html>

<head>

<title>
IPT10 Laboratory Activity #2
</title>

<link rel="stylesheet" href="https://assets.ubuntu.com/v1/vanilla-framework-version-4.15.0.min.css">

</head>


<body>


<section class="p-section--hero">


<h1>
Registration (Step 2/3)
</h1>


<form action="step-3.php" method="POST">


<label>
Program
</label>

<input
type="text"
name="program"
placeholder="BS Information Technology"
required>



<label>
Complete Address
</label>

<textarea
name="address"
rows="3"
required></textarea>



<br><br>


<button type="submit">
Next
</button>


</form>


</section>


</body>

</html>