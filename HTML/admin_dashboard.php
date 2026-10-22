
<?php
// Show errors during local development only
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "auth.php";
require_once "db.php";

// Admin-only access
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: dashboard.php");
    exit;
}

// Count total users
$userQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM student_accounts"
);

// Count students
$studentQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM student_accounts
     WHERE role = 'student'"
);

// Count admins
$adminQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM student_accounts
     WHERE role = 'admin'"
);

// Count events using events1
$eventQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM events1"
);

// Check database queries
if (
    !$userQuery ||
    !$studentQuery ||
    !$adminQuery ||
    !$eventQuery
) {
    error_log("Dashboard statistics query failed: " . mysqli_error($conn));
    http_response_code(500);
    exit("Could not load dashboard statistics. Please check your database tables.");
}

$totalUsers = mysqli_fetch_assoc($userQuery)["total"];
$totalStudents = mysqli_fetch_assoc($studentQuery)["total"];
$totalAdmins = mysqli_fetch_assoc($adminQuery)["total"];
$totalEvents = mysqli_fetch_assoc($eventQuery)["total"];

// Latest student registrations
$latestQuery = mysqli_query(
    $conn,
    "SELECT registration_id, firstname, lastname,
            email, course, registration_date
     FROM student_accounts
     WHERE role = 'student'
     ORDER BY registration_id DESC
     LIMIT 5"
);

if (!$latestQuery) {
    error_log("Latest registrations query failed: " . mysqli_error($conn));
    http_response_code(500);
    exit("Could not load latest registrations.");
}

$latestRegistrations = [];

while ($row = mysqli_fetch_assoc($latestQuery)) {
    $latestRegistrations[] = $row;
}

// Safe HTML output
function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$username = $_SESSION["username"] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StudentHub Admin Dashboard</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #F0F4F8;
            color: #222;
        }

        header {
            background: #001f3f;
            color: white;
            padding: 22px 5%;
        }

        header h1 {
            margin: 0;
            font-size: 25px;
        }

        header p {
            margin-bottom: 0;
        }

        nav {
            background: #004b87;
            padding: 14px 5%;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            width: 94%;
            max-width: 1200px;
            margin: 25px auto;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h2 {
            color: #001f3f;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #555;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 9px;
            border-left: 4px solid #004b87;
            box-shadow: 0 2px 8px #0000000d;
        }

        .stat-card h3 {
            font-size: 15px;
            color: #555;
            margin: 0 0 14px;
        }

        .stat-card p {
            font-size: 32px;
            font-weight: bold;
            color: #001f3f;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .registrations {
            background: white;
            padding: 22px;
            border-radius: 9px;
            box-shadow: 0 2px 8px #0000000d;
        }

        .registrations h2 {
            margin-top: 0;
            color: #001f3f;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #004b87;
            color: white;
        }

        tbody tr:nth-child(even) {
            background: #f5f8fb;
        }

        .empty {
            text-align: center;
            padding: 20px;
        }

        footer {
            background: #001f3f;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 35px;
        }

        @media (max-width: 850px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            header {
                padding: 18px 4%;
            }

            header h1 {
                font-size: 21px;
            }

            nav {
                padding: 12px 4%;
                gap: 14px;
            }

            main {
                width: 94%;
                margin: 18px auto;
            }

            .stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 18px;
            }

            .registrations {
                padding: 14px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>STUDENT HUB PORTAL</h1>
    <p>Admin Dashboard</p>
</header>

<nav>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="students.php">Manage Students</a>
    <a href="events.php">Manage Events</a>
    <a href="events_api.html">Events API</a>
    <a href="logout.php">Logout</a>
</nav>

<main>

    <section class="welcome">
        <h2>Welcome, <?= e($username) ?>!</h2>
        <p>You are logged in as an administrator.</p>
        <p>Here is your StudentHub portal overview.</p>
    </section>

    <section class="stats">

        <div class="stat-card">
            <h3>Total Users</h3>
            <p><?= e($totalUsers) ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Students</h3>
            <p><?= e($totalStudents) ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Events</h3>
            <p><?= e($totalEvents) ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Admins</h3>
            <p><?= e($totalAdmins) ?></p>
        </div>

    </section>

    <section class="registrations">

        <h2>Latest Registrations</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($latestRegistrations) > 0): ?>

                    <?php foreach ($latestRegistrations as $student): ?>
                        <tr>
                            <td><?= e($student["registration_id"]) ?></td>

                            <td>
                                <?= e(
                                    trim(
                                        ($student["firstname"] ?? "") . " " .
                                        ($student["lastname"] ?? "")
                                    )
                                ) ?>
                            </td>

                            <td><?= e($student["email"]) ?></td>

                            <td><?= e($student["course"]) ?></td>

                            <td>
                                <?= e($student["registration_date"] ?? "N/A") ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="empty">
                            No student registrations found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </section>

</main>

<footer>
    <p>StudentHub Portal | Admin Dashboard</p>
</footer>

</body>
</html>
