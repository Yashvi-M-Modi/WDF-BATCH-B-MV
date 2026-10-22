
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

$message = "";

// Show session timeout message
if (isset($_GET["timeout"])) {
    $message = "Your session expired. Please log in again.";
}

// Handle login form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $message = "Please enter your email and password.";

    } else {

        // Correct table: student_accounts
        $sql = "SELECT registration_id, username, email,
                       password, role
                FROM student_accounts
                WHERE email = ?
                LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            error_log(mysqli_error($conn));
            $message = "Unable to process your login.";

        } else {

            mysqli_stmt_bind_param($stmt, "s", $email);

            if (mysqli_stmt_execute($stmt)) {

                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);

                // Verify password
                if (
                    $user &&
                    password_verify($password, $user["password"])
                ) {

                    // Validate account role
                    if (
                        $user["role"] !== "admin" &&
                        $user["role"] !== "student"
                    ) {
                        $message = "Invalid account role.";

                    } elseif (!session_regenerate_id(true)) {

                        error_log("Session regeneration failed.");
                        $message = "Unable to start your session.";

                    } else {

                        // Save login details in session
                        $_SESSION["user_id"] =
                            $user["registration_id"];

                        $_SESSION["username"] =
                            $user["username"];

                        $_SESSION["email"] =
                            $user["email"];

                        $_SESSION["role"] =
                            $user["role"];

                        $_SESSION["last_activity"] = time();

                        // Close database resources
                        mysqli_stmt_close($stmt);
                        mysqli_close($conn);

                        // Redirect according to role
                        if ($user["role"] === "admin") {
                            header("Location: admin_dashboard.php");
                            exit;
                        }

                        header("Location: homepage.html");
                        exit;
                    }

                } else {
                    $message = "Invalid email or password.";
                }

            } else {

                error_log(mysqli_stmt_error($stmt));
                $message = "Login failed. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - StudentHub</title>

    <link rel="stylesheet" href="../CSS/login.css">
</head>

<body>

    <div class="login">

        <div class="left">
            <h1>STUDENT HUB</h1>
            <p>Your learning journey starts here.</p>
        </div>

        <div class="right">

            <h2>Login</h2>

            <?php if ($message !== "") { ?>
                <p class="message" role="alert">
                    <?php
                    echo htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>
            <?php } ?>

            <form action="login.php" method="post">

                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    autocomplete="username"
                    maxlength="255"
                    required
                >

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button type="submit">Login</button>

            </form>

            <p>
                Don't have an account?
                <a href="form.html">Register here</a>
            </p>

        </div>
    </div>

</body>
</html>
