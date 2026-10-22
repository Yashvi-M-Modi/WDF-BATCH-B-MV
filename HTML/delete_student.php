<?php
require_once __DIR__ . "/auth.php";

// Admin-only access
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    http_response_code(403);
    exit("Access denied.");
}

require_once __DIR__ . "/db.php";

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Method not allowed.");
}

// Verify CSRF token
if (
    !isset($_POST["csrf_token"], $_SESSION["csrf_token"]) ||
    !is_string($_POST["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    http_response_code(403);
    exit("Invalid request. Please go back and try again.");
}

// Validate student ID
$studentId = filter_input(
    INPUT_POST,
    "registration_id",
    FILTER_VALIDATE_INT
);

if (!$studentId || $studentId <= 0) {
    header(
        "Location: students.php?message=" .
        urlencode("Invalid student ID.")
    );
    exit;
}

// Prevent deleting own account
if ((int)$studentId === (int)$_SESSION["user_id"]) {
    header(
        "Location: students.php?message=" .
        urlencode("You cannot delete your own account.")
    );
    exit;
}

// Delete student only
$sql = "DELETE FROM student_accounts
        WHERE registration_id = ?
        AND role = 'student'";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    error_log("Student deletion query preparation failed.");
    http_response_code(500);
    exit("Unable to process deletion.");
}

mysqli_stmt_bind_param($stmt, "i", $studentId);

if (mysqli_stmt_execute($stmt)) {

    if (mysqli_stmt_affected_rows($stmt) > 0) {

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        header(
            "Location: students.php?message=" .
            urlencode("Student deleted successfully.")
        );
        exit;

    } else {

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        header(
            "Location: students.php?message=" .
            urlencode("Student not found.")
        );
        exit;
    }

} else {

    error_log("Student deletion failed.");

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    http_response_code(500);
    exit("Unable to delete student.");
}
?>