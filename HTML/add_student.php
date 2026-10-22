
<?php
require_once "auth.php";
require_once "db.php";

// Admin-only access
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

$message = "";

$firstname = "";
$middlename = "";
$lastname = "";
$username = "";
$email = "";
$mobile = "";
$course = "";
$gender = "";

// Allowed values
$validCourses = ["BCA", "BBA", "BSc", "BCom", "BTech"];
$validGenders = ["Male", "Female", "Other"];

// CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION["csrf_token"], $submittedToken)
    ) {
        http_response_code(403);
        exit("Invalid request. Please reload the page.");
    }

    // Collect form data
    $firstname = trim($_POST["firstname"] ?? "");
    $middlename = trim($_POST["middlename"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $password = $_POST["password"] ?? "";

    // Validate input
    if (
        !is_string($password) ||
        !preg_match("/^[A-Za-z]+$/", $firstname) ||
        ($middlename !== "" &&
            !preg_match("/^[A-Za-z]+$/", $middlename)) ||
        !preg_match("/^[A-Za-z]+$/", $lastname) ||
        !preg_match("/^[A-Za-z0-9_]{3,20}$/", $username) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !preg_match("/^[0-9]{10}$/", $mobile) ||
        !in_array($course, $validCourses, true) ||
        !in_array($gender, $validGenders, true) ||
        strlen($password) < 8 ||
        strlen($password) > 4096
    ) {
        $message = "Please enter valid details. Password must be at least 8 characters.";

    } else {

        // Check duplicate email or username
        $check = mysqli_prepare(
            $conn,
            "SELECT registration_id
             FROM student_accounts
             WHERE email = ? OR username = ?
             LIMIT 1"
        );

        if (!$check) {
            error_log("Student duplicate check preparation failed.");
            http_response_code(500);
            exit("Could not process the request.");
        }

        mysqli_stmt_bind_param(
            $check,
            "ss",
            $email,
            $username
        );

        if (!mysqli_stmt_execute($check)) {
            mysqli_stmt_close($check);
            error_log("Student duplicate check failed.");
            http_response_code(500);
            exit("Could not process the request.");
        }

        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "That email or username is already in use.";
            mysqli_stmt_close($check);

        } else {

            mysqli_stmt_close($check);

            // Hash password
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if ($passwordHash === false) {
                http_response_code(500);
                exit("Could not securely create the account.");
            }

            // Insert student
            $sql = "INSERT INTO student_accounts
                    (firstname, middlename, lastname, username,
                     email, mobile, password, course, gender, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                error_log("Student insert preparation failed.");
                http_response_code(500);
                exit("Could not create the student account.");
            }

            mysqli_stmt_bind_param(
                $stmt,
                "sssssssss",
                $firstname,
                $middlename,
                $lastname,
                $username,
                $email,
                $mobile,
                $passwordHash,
                $course,
                $gender
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);
                mysqli_close($conn);

                // Prevent form resubmission
                header(
                    "Location: students.php?message=" .
                    urlencode("Student record added successfully.")
                );
                exit;

            } else {

                // Duplicate key may occur if two requests
                // attempt to create the same account together.
                if (mysqli_stmt_errno($stmt) === 1062) {
                    $message = "That email or username is already in use.";
                } else {
                    error_log("Student account insert failed.");
                    $message = "Failed to add student. Please try again.";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Safe HTML output
function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Student | StudentHub</title>

    <link rel="stylesheet" href="../CSS/students.css">
</head>
<body>

<div class="container form-container">

    <h1>Add Student</h1>

    <?php if ($message !== "") { ?>
        <p class="message" role="alert">
            <?= e($message) ?>
        </p>
    <?php } ?>

    <form method="post" action="add_student.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION["csrf_token"]) ?>"
        >

        <label for="firstname">First Name</label>
        <input
            type="text"
            id="firstname"
            name="firstname"
            value="<?= e($firstname) ?>"
            pattern="[A-Za-z]+"
            maxlength="100"
            autocomplete="given-name"
            required
        >

        <label for="middlename">Middle Name (optional)</label>
        <input
            type="text"
            id="middlename"
            name="middlename"
            value="<?= e($middlename) ?>"
            pattern="[A-Za-z]+"
            maxlength="100"
            autocomplete="additional-name"
        >

        <label for="lastname">Last Name</label>
        <input
            type="text"
            id="lastname"
            name="lastname"
            value="<?= e($lastname) ?>"
            pattern="[A-Za-z]+"
            maxlength="100"
            autocomplete="family-name"
            required
        >

        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            value="<?= e($username) ?>"
            minlength="3"
            maxlength="20"
            pattern="[A-Za-z0-9_]{3,20}"
            autocomplete="username"
            required
        >

        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?= e($email) ?>"
            maxlength="254"
            autocomplete="email"
            required
        >

        <label for="mobile">Mobile Number</label>
        <input
            type="tel"
            id="mobile"
            name="mobile"
            value="<?= e($mobile) ?>"
            maxlength="10"
            inputmode="numeric"
            pattern="[0-9]{10}"
            autocomplete="tel-national"
            required
        >

        <label for="password">Temporary Password</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            maxlength="4096"
            autocomplete="new-password"
            required
        >

        <label for="course">Course</label>
        <select id="course" name="course" required>
            <option value="">Select Course</option>

            <?php foreach ($validCourses as $item) { ?>
                <option
                    value="<?= e($item) ?>"
                    <?= $course === $item ? "selected" : "" ?>
                >
                    <?= e($item) ?>
                </option>
            <?php } ?>
        </select>

        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
            <option value="">Select Gender</option>

            <?php foreach ($validGenders as $item) { ?>
                <option
                    value="<?= e($item) ?>"
                    <?= $gender === $item ? "selected" : "" ?>
                >
                    <?= e($item) ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit">Add Student</button>

        <a class="button secondary" href="students.php">
            Cancel
        </a>

    </form>

</div>

</body>
</html>

<?php
mysqli_close($conn);
?>
