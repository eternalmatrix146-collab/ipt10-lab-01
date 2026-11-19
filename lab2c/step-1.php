<?php

require "helpers/helper-functions.php";

session_start();

?>

<html>
<head>
    <meta charset="utf-8">
    <title>IPT10 Laboratory Activity #2</title>
    <link rel="icon" href="https://phpsandbox.io/assets/img/brand/phpsandbox.png">
    <link rel="stylesheet" href="https://assets.ubuntu.com/v1/vanilla-framework-version-4.15.0.min.css">
</head>

<body>

<section class="p-section--hero">

<div class="row--50-50-on-large">

<div class="col">

<h1>Registration (Step 1/3)</h1>

<form action="step-2.php" method="POST">

<fieldset>

<label>Complete Name</label>
<input 
type="text"
name="fullname"
placeholder="John Doe"
required>


<label>Birthday</label>
<input
type="date"
name="birthday"
required>


<label>Contact Number</label>
<input
type="text"
name="contact_number"
placeholder="09XXXXXXXXX"
required>


<label>Sex</label>

<select name="sex" required>

<option value="">-- Select Sex --</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Prefer not to say">Prefer not to say</option>

</select>


<br><br>

<button type="submit">
Next
</button>


</fieldset>

</form>

</div>

</div>

</section>

</body>
</html>