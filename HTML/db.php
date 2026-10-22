
<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "studenthub";

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please check your settings.");
}

mysqli_set_charset($conn, "utf8mb4");
?>
