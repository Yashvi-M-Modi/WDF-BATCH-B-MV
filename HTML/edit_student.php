<?php
require_once "auth.php";

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: students.php?message=" . urlencode("Invalid student ID."));
    exit;
}

$message = "";

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Fetch student details
$stmt = mysqli_prepare(
    $conn,
    "SELECT firstname, middlename, lastname, username,
            email, mobile, course, gender
     FROM student_accounts
     WHERE registration_id = ? AND role = 'student'"
);

if (!$stmt) {
    die("Database query failed.");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: students.php?message=" . urlencode("Student record not found."));
    exit;
}

// Update student
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Invalid request. Please go back and try again.");
    }

    $firstname = trim($_POST["firstname"] ?? "");
    $middlename = trim($_POST["middlename"] ?? "");
    $lastname = trim($_POST["lastname"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $gender = trim($_POST["gender"] ?? "");

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
        !in_array($course, $validCourses, true) ||
        !in_array($gender, $validGenders, true)
    ) {
        $message = "Please check the details and try again.";

    } else {

        // Check duplicate email or username
        $check = mysqli_prepare(
            $conn,
            "SELECT registration_id
             FROM student_accounts
             WHERE (email = ? OR username = ?)
             AND registration_id <> ?
             AND role = 'student'"
        );

        if (!$check) {
            die("Database query failed.");
        }

        mysqli_stmt_bind_param(
            $check,
            "ssi",
            $email,
            $username,
            $id
        );

        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "That email or username belongs to another student.";

        } else {

            $sql = "UPDATE student_accounts
                    SET firstname = ?,
                        middlename = ?,
                        lastname = ?,
                        username = ?,
                        email = ?,
                        mobile = ?,
                        course = ?,
                        gender = ?
                    WHERE registration_id = ?
                    AND role = 'student'";

            $update = mysqli_prepare($conn, $sql);

            if (!$update) {
                mysqli_stmt_close($check);
                die("Database update failed.");
            }

            mysqli_stmt_bind_param(
                $update,
                "ssssssssi",
                $firstname,
                $middlename,
                $lastname,
                $username,
                $email,
                $mobile,
                $course,
                $gender,
                $id
            );

            if (mysqli_stmt_execute($update)) {

                mysqli_stmt_close($update);
                mysqli_stmt_close($check);

                header(
                    "Location: students.php?message=" .
                    urlencode("Student record updated successfully.")
                );
                exit;

            } else {
                $message = "Failed to update the student record.";
            }

            mysqli_stmt_close($update);
        }

        mysqli_stmt_close($check);
    }

    // Preserve entered values if validation fails
    $student = [
        "firstname" => $firstname,
        "middlename" => $middlename,
        "lastname" => $lastname,
        "username" => $username,
        "email" => $email,
        "mobile" => $mobile,
        "course" => $course,
        "gender" => $gender
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | StudentHub</title>
    <link rel="stylesheet" href="../CSS/students.css">
</head>
<body>

<div class="container form-container">

    <h1>Edit Student</h1>

    <?php if ($message !== "") { ?>
        <p class="message">
            <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php } ?>

    <form method="post"
          action="edit_student.php?id=<?php echo $id; ?>">

        <input type="hidden"
               name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

        <label for="firstname">First Name</label>
        <input type="text" id="firstname" name="firstname"
               value="<?php echo htmlspecialchars($student["firstname"], ENT_QUOTES, "UTF-8"); ?>"
               required>

        <label for="middlename">Middle Name</label>
        <input type="text" id="middlename" name="middlename"
               value="<?php echo htmlspecialchars($student["middlename"], ENT_QUOTES, "UTF-8"); ?>">

        <label for="lastname">Last Name</label>
        <input type="text" id="lastname" name="lastname"
               value="<?php echo htmlspecialchars($student["lastname"], ENT_QUOTES, "UTF-8"); ?>"
               required>

        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               value="<?php echo htmlspecialchars($student["username"], ENT_QUOTES, "UTF-8"); ?>"
               required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?php echo htmlspecialchars($student["email"], ENT_QUOTES, "UTF-8"); ?>"
               required>

        <label for="mobile">Mobile Number</label>
        <input type="text" id="mobile" name="mobile"
               value="<?php echo htmlspecialchars($student["mobile"], ENT_QUOTES, "UTF-8"); ?>"
               maxlength="10" pattern="[0-9]{10}" required>

        <label for="course">Course</label>
        <select id="course" name="course" required>
            <?php foreach (["BCA", "BBA", "BSc", "BCom", "BTech"] as $item) { ?>
                <option value="<?php echo $item; ?>"
                    <?php echo $student["course"] === $item ? "selected" : ""; ?>>
                    <?php echo $item; ?>
                </option>
            <?php } ?>
        </select>

        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
            <?php foreach (["Male", "Female", "Other"] as $item) { ?>
                <option value="<?php echo $item; ?>"
                    <?php echo $student["gender"] === $item ? "selected" : ""; ?>>
                    <?php echo $item; ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit">Save Changes</button>
        <a class="button secondary" href="students.php">Cancel</a>

    </form>
</div>

</body>
</html>
