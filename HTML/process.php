<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $firstname = trim($_POST["firstname"]);
    $middlename = trim($_POST["middlename"]);
    $lastname = trim($_POST["lastname"]);
    $email = trim($_POST["email"]);
    $mobile = trim($_POST["mobile"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];
    $course = $_POST["course"];
    $gender = $_POST["gender"] ?? "";

    if (!preg_match("/^[A-Za-z]+$/", $firstname)) {
        echo "Invalid first name";
        exit;
    }

    if (!preg_match("/^[A-Za-z]+$/", $middlename)) {
        echo "Invalid middle name";
        exit;
    }

    if (!preg_match("/^[A-Za-z]+$/", $lastname)) {
        echo "Invalid last name";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email";
        exit;
    }

    if (!preg_match("/^[0-9]{10}$/", $mobile)) {
        echo "Mobile number must have 10 digits";
        exit;
    }

    if (strlen($password) < 8) {
        echo "Password must have at least 8 characters";
        exit;
    }

    if (!preg_match("/[!@#$%^&*]/", $password)) {
        echo "Password must contain a special character";
        exit;
    }

    if ($password != $confirm) {
        echo "Passwords do not match";
        exit;
    }

    if ($course == "") {
        echo "Please select a course";
        exit;
    }

    if ($gender == "") {
        echo "Please select a gender";
        exit;
    }

    $file = __DIR__ . "/../JSON/registrations.json";

    $registrations = [];

    if (file_exists($file)) {
        $registrations = json_decode(file_get_contents($file), true);

        if (!is_array($registrations)) {
            $registrations = [];
        }
    }

    $newRegistration = [
        "firstname" => $firstname,
        "middlename" => $middlename,
        "lastname" => $lastname,
        "email" => $email,
        "mobile" => $mobile,
        "password" => password_hash($password, PASSWORD_DEFAULT),
        "course" => $course,
        "gender" => $gender
    ];

    $registrations[] = $newRegistration;

    $result = file_put_contents(
        $file,
        json_encode($registrations, JSON_PRETTY_PRINT)
    );

    if ($result === false) {
        echo "Error: Could not save registration";
        exit;
    }

    echo "<h2>Registration Successful!</h2>";
    echo "<p>Redirecting to login page...</p>";

    echo "<script>
        setTimeout(function() {
            window.location.href = 'login.html';
        }, 2000);
    </script>";
}

?>