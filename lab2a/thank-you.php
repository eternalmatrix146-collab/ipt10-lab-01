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


</body>

</html>