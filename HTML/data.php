```php
<?php
$conn = mysqli_connect("localhost", "root", "", "studenthub");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST["firstname"] ?? "");
    $middlename = trim($_POST["middlename"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    $course = $_POST["course"] ?? "";
    $gender = $_POST["gender"] ?? "";

    if (
        $firstname == "" || $middlename == "" || $lastname == "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !preg_match("/^[0-9]{10}$/", $mobile) ||
        strlen($password) < 8 ||
        !preg_match("/[!@#$%^&*]/", $password) ||
        $password !== $confirm ||
        $course == "" ||
        !in_array($gender, ["Male", "Female", "Other"], true) ||
        !isset($_POST["terms"])
    ) {
        die("Invalid details. Please check your form.");
    }

    $create = "CREATE TABLE IF NOT EXISTS student_registrations (
        registration_id INT AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(100) NOT NULL,
        middlename VARCHAR(100) NOT NULL,
        lastname VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        mobile VARCHAR(10) NOT NULL,
        password VARCHAR(255) NOT NULL,
        course VARCHAR(50) NOT NULL,
        gender VARCHAR(20) NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!mysqli_query($conn, $create)) {
        die("Table creation failed: " . mysqli_error($conn));
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO student_registrations (firstname, middlename, lastname, email, mobile, password, course, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "ssssssss", $firstname, $middlename, $lastname, $email, $mobile, $hashedPassword, $course, $gender);

    if (!mysqli_stmt_execute($stmt)) {
        die("MySQL error: " . mysqli_stmt_error($stmt));
    }

    $jsonFile = __DIR__ . "/registrations.json";

    $registration = [
        "firstname" => $firstname,
        "middlename" => $middlename,
        "lastname" => $lastname,
        "email" => $email,
        "mobile" => $mobile,
        "password" => $hashedPassword,
        "course" => $course,
        "gender" => $gender,
        "registration_date" => date("Y-m-d H:i:s")
    ];

    $registrations = [];

    if (file_exists($jsonFile)) {
        $content = file_get_contents($jsonFile);
        $registrations = json_decode($content, true);

        if (!is_array($registrations)) {
            $registrations = [];
        }
    }

    $registrations[] = $registration;

    if (file_put_contents(
        $jsonFile,
        json_encode($registrations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    ) === false) {
        echo "Saved in MySQL, but could not save to JSON. Check folder permissions.";
    } else {
        echo "<h2>Registration successful!</h2>";
        echo "<p>Your details were saved in MySQL and JSON.</p>";
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
```
