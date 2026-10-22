<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $file = __DIR__ . "/../JSON/registrations.json";

    if (!file_exists($file)) {
        echo "No registration data found";
        exit;
    }

    $registrations = json_decode(file_get_contents($file), true);

    if (!is_array($registrations)) {
        echo "Invalid registration data";
        exit;
    }

    $loginSuccess = false;

    foreach ($registrations as $user) {

        if ($user["email"] == $email) {

            if (password_verify($password, $user["password"])) {
                $loginSuccess = true;
                break;
            }
        }
    }

    if ($loginSuccess) {
        echo "<h2>Login Successful!</h2>";
        echo "<p>Redirecting to homepage...</p>";

        echo "<script>
            setTimeout(function() {
                window.location.href = 'homepage.html';
            }, 3000);
        </script>";
    } else {
        echo "<h2>Invalid email or password</h2>";
        echo "<p>Please try again.</p>";
    }
}

?>