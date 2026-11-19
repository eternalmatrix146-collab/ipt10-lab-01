<?php
// config/db.sample.php
// SAMPLE settings file. Copy this file, name the copy "db.php",
// then put your own database user and password in the copy.
// db.php is listed in .gitignore so real passwords are never pushed to GitHub.
require_once __DIR__ . '/../classes/Database.php';

$dsn  = "mysql:host=localhost;dbname=training_db;charset=utf8mb4";
$user = "root";   // XAMPP default user
$pass = "";       // XAMPP default password is empty

// The single administrator account used by login.php.
// Default login: admin / admin123
// To change the password, run this in a terminal and paste the result below:
//   php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"
$admin_user      = "admin";
$admin_pass_hash = '$2y$10$xxOiomrfnaKULnNRGbt.J.FfAcKS9o/OTtaRI/LqveO4hMP/RboEC';

// Obtain the single shared PDO connection (Singleton).
try {
    $db = Database::getInstance($dsn, $user, $pass);
} catch (PDOException $e) {
    exit("Could not connect to the database. Start MySQL in XAMPP, "
       . "import sql/schema.sql, and check the settings in config/db.php.");
}
