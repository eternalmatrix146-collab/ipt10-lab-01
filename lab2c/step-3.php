<?php

require "helpers/helper-functions.php";

session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {


if (
empty($_POST['program']) ||
empty($_POST['address'])
){

header("Location: step-2.php");
exit();

}


$_SESSION['program'] = $_POST['program'];
$_SESSION['address'] = $_POST['address'];

}


// Only block access if there's still no data in the session at all
// (e.g. someone opened step-3.php directly without going through step-2).
if (!isset($_SESSION['program'])) {

header("Location: step-2.php");
exit();

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


<h1>
Registration (Step 3/3)
</h1>


<form action="thank-you.php" method="POST">


<label>
Email Address
</label>

<input
type="email"
name="email"
placeholder="example@email.com"
required>


<label>
Password
</label>

<input
type="password"
name="password"
required>


<label>

<input
type="checkbox"
name="agree"
value="yes"
required>

I agree to the Terms and Conditions

</label>


<br><br>


<button type="submit">
Finish
</button>


</form>


</body>

</html>