<?php
$conn = mysqli_connect("localhost", "root", "", "studenthub");

if (!$conn) {
    error_log("Database connection failed.");
    http_response_code(500);
    exit("Unable to connect to the database.");
}

mysqli_set_charset($conn, "utf8mb4");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstname = trim($_POST["firstname"] ?? "");
    $middlename = trim($_POST["middlename"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    $course = $_POST["course"] ?? "";
    $gender = $_POST["gender"] ?? "";

    // Public registrations are always students
    $role = "student";

    $validCourses = ["BCA", "BBA", "BSc", "BCom", "BTech"];
    $validGenders = ["Male", "Female", "Other"];

    if (
        !preg_match("/^[A-Za-z]+$/", $firstname) ||
        ($middlename !== "" &&
         !preg_match("/^[A-Za-z]+$/", $middlename)) ||
        !preg_match("/^[A-Za-z]+$/", $lastname) ||
        !preg_match("/^[A-Za-z0-9_]{3,20}$/", $username) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !preg_match("/^[0-9]{10}$/", $mobile) ||
        !is_string($password) ||
        strlen($password) < 8 ||
        !preg_match("/[!@#$%^&*]/", $password) ||
        !is_string($confirm) ||
        $password !== $confirm ||
        !in_array($course, $validCourses, true) ||
        !in_array($gender, $validGenders, true) ||
        !isset($_POST["terms"])
    ) {
        $message = "Invalid details. Please check all fields and try again.";

    } else {

        // Check duplicate email or username
        $check = mysqli_prepare(
            $conn,
            "SELECT registration_id
             FROM student_accounts
             WHERE email = ? OR username = ?"
        );

        if (!$check) {
            error_log("Registration duplicate-check preparation failed.");
            $message = "Unable to process registration.";

        } else {

            mysqli_stmt_bind_param(
                $check,
                "ss",
                $email,
                $username
            );

            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {

                $message = "Email or username already exists. Please use another.";

            } else {

                // Hash password securely
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($hashedPassword === false) {

                    $message = "Unable to create your account securely.";

                } else {

                    // Insert into the correct table
                    $sql = "INSERT INTO student_accounts
                            (firstname, middlename, lastname, username,
                             email, mobile, password, course, gender, role)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = mysqli_prepare($conn, $sql);

                    if (!$stmt) {
                        error_log("Registration insert preparation failed.");
                        $message = "Unable to prepare registration.";

                    } else {

                        mysqli_stmt_bind_param(
                            $stmt,
                            "ssssssssss",
                            $firstname,
                            $middlename,
                            $lastname,
                            $username,
                            $email,
                            $mobile,
                            $hashedPassword,
                            $course,
                            $gender,
                            $role
                        );

                        if (mysqli_stmt_execute($stmt)) {

                            // MySQL registration succeeded
                            $registrationId = mysqli_insert_id($conn);

                            // JSON backup (non-sensitive fields only)
                            $jsonFile = __DIR__ . "/../JSON/registrations.json";

                            $registration = [
                                "registration_id" => $registrationId,
                                "firstname" => $firstname,
                                "middlename" => $middlename,
                                "lastname" => $lastname,
                                "username" => $username,
                                "email" => $email,
                                "mobile" => $mobile,
                                "course" => $course,
                                "gender" => $gender,
                                "role" => $role,
                                "registration_date" => date("Y-m-d H:i:s")
                            ];

                            // Use a separate lock file to protect
                            // concurrent read-modify-write operations
                            $lockFile = $jsonFile . ".lock";
                            $lock = @fopen($lockFile, "c");

                            $jsonSaved = false;

                            if ($lock !== false) {

                                if (flock($lock, LOCK_EX)) {

                                    $registrations = [];

                                    if (file_exists($jsonFile)) {

                                        $existingData = file_get_contents($jsonFile);

                                        $existing = json_decode(
                                            $existingData,
                                            true
                                        );

                                        if (
                                            !is_array($existing) ||
                                            json_last_error() !== JSON_ERROR_NONE
                                        ) {
                                            $existing = null;
                                        }

                                        if ($existing === null) {
                                            error_log("Registration JSON file is invalid.");
                                        } else {
                                            $registrations = $existing;
                                        }
                                    }

                                    // Do not overwrite an invalid existing file
                                    if (
                                        !file_exists($jsonFile) ||
                                        is_array($existing ?? null)
                                    ) {

                                        $registrations[] = $registration;

                                        $jsonData = json_encode(
                                            $registrations,
                                            JSON_PRETTY_PRINT |
                                            JSON_UNESCAPED_SLASHES
                                        );

                                        if ($jsonData !== false) {

                                            $written = file_put_contents(
                                                $jsonFile,
                                                $jsonData,
                                                LOCK_EX
                                            );

                                            if ($written !== false) {
                                                $jsonSaved = true;
                                            }
                                        }
                                    }

                                    flock($lock, LOCK_UN);
                                }

                                fclose($lock);
                            }

                            if ($jsonSaved) {
                                $message = "Registration successful! Your details were saved in MySQL and JSON.";
                            } else {
                                error_log("Registration saved in MySQL but JSON saving failed.");
                                $message = "Registration saved in MySQL, but JSON saving failed. Your account was still created.";
                            }

                        } else {

                            // Unique constraint may reject duplicates
                            if (mysqli_stmt_errno($stmt) === 1062) {
                                $message = "Email or username already exists. Please use another.";
                            } else {
                                error_log("Registration insert failed.");
                                $message = "Registration failed. Please try again.";
                            }
                        }

                        mysqli_stmt_close($stmt);
                    }
                }
            }

            mysqli_stmt_close($check);
        }
    }

} else {
    $message = "Please submit the registration form.";
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status</title>
</head>
<body>

    <h2>
        <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
    </h2>

    <p><a href="form.html">Return to Registration</a></p>
    <p><a href="login.php">Go to Login</a></p>

</body>
</html>