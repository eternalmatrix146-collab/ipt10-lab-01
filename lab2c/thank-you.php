<?php

require "helpers/helper-functions.php";

session_start();



if (
empty($_POST['email']) ||
empty($_POST['password']) ||
!isset($_POST['agree'])
){

header("Location: step-3.php");
exit();

}



$_SESSION['email'] = $_POST['email'];

$_SESSION['password'] = password_hash(
$_POST['password'],
PASSWORD_DEFAULT
);


$_SESSION['agree'] = "Yes";



$formattedBirthday = date(
"F d, Y",
strtotime($_SESSION['birthday'])
);



$birthDate = new DateTime($_SESSION['birthday']);

$today = new DateTime();

$age = $birthDate->diff($today)->y;



$form_data = $_SESSION;


$form_data['birthday'] = $formattedBirthday;

$form_data['age'] = $age;



define('REGISTRATIONS_FILE_PATH', 'registrations.csv');

// Build the row in the exact column order required for display:
// Complete Name, Birthday, Age, Contact Number, Sex, Program, Complete Address, Email Address
$csv_row = [
    $_SESSION['fullname'],
    $formattedBirthday,
    $age,
    $_SESSION['contact_number'],
    $_SESSION['sex'],
    $_SESSION['program'],
    $_SESSION['address'],
    $_SESSION['email'],
];

// 'a' = append mode, so every new registrant is added to the end of the file
// instead of overwriting existing rows.
$opened_file_handler = fopen(REGISTRATIONS_FILE_PATH, 'a');
fputcsv($opened_file_handler, $csv_row);
fclose($opened_file_handler);



session_destroy();


?>


<html>

<head>

<title>
Thank You Page
</title>


<link rel="stylesheet" href="https://assets.ubuntu.com/v1/vanilla-framework-version-4.15.0.min.css">

</head>


<body>


<h1>
Thank You Page
</h1>



<table>


<tr>

<th>
Field
</th>

<th>
Value
</th>

</tr>



<?php foreach($form_data as $key=>$val): ?>


<tr>


<td>

<?php

echo ucwords(
str_replace("_"," ",$key)
);

?>

</td>



<td>


<?php

if($key=="password"){

echo "********";

}

else{

echo htmlspecialchars($val);

}

?>


</td>



</tr>


<?php endforeach; ?>


</table>

<p>
<a href="registrants.php">View all registrants</a>
</p>

</body>

</html>