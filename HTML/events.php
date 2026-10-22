
<?php
require_once "auth.php";
require_once "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access Denied. Admin access required.");
}

if (empty($_SESSION["event_csrf"])) {
    $_SESSION["event_csrf"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";
$uploadDir = __DIR__ . "/event_posters/";

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        exit("Could not create the poster folder.");
    }
}

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function savePoster($field, $uploadDir) {
    if (!isset($_FILES[$field]) || $_FILES[$field]["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Poster upload failed.");
    }

    if ($file["size"] > 2 * 1024 * 1024) {
        throw new Exception("Poster must be 2 MB or smaller.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $types = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($types[$mime]) || !getimagesize($file["tmp_name"])) {
        throw new Exception("Only JPG, PNG and WebP images are allowed.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $types[$mime];

    if (!move_uploaded_file($file["tmp_name"], $uploadDir . $filename)) {
        throw new Exception("Could not save the poster.");
    }

    return $filename;
}

function removePoster($filename, $uploadDir) {
    if ($filename && basename($filename) === $filename) {
        $path = $uploadDir . $filename;

        if (is_file($path)) {
            unlink($path);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals($_SESSION["event_csrf"], $_POST["csrf_token"])
        ) {
            throw new Exception("Invalid request. Refresh the page and try again.");
        }

        $action = $_POST["action"] ?? "";

        if ($action === "delete") {
            $id = filter_var($_POST["event_id"] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new Exception("Invalid event ID.");
            }

            $stmt = mysqli_prepare($conn, "SELECT poster FROM events1 WHERE event_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $event = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$event) {
                throw new Exception("Event not found.");
            }

            $stmt = mysqli_prepare($conn, "DELETE FROM events1 WHERE event_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            removePoster($event["poster"], $uploadDir);
            $message = "Event deleted successfully.";
        }

        elseif ($action === "save") {
            $id = (int)($_POST["event_id"] ?? 0);
            $title = trim($_POST["title"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $date = $_POST["event_date"] ?? "";
            $time = $_POST["event_time"] ?? "";
            $venue = trim($_POST["venue"] ?? "");

            if (
                $title === "" || strlen($title) > 150 ||
                $description === "" || strlen($venue) > 200 ||
                $venue === ""
            ) {
                throw new Exception("Please enter valid event details.");
            }

            $dateObj = DateTime::createFromFormat("!Y-m-d", $date);
            $timeObj = DateTime::createFromFormat("!H:i", $time);

            if (
                !$dateObj || $dateObj->format("Y-m-d") !== $date ||
                !$timeObj || $timeObj->format("H:i") !== $time
            ) {
                throw new Exception("Please enter a valid date and time.");
            }

            $newPoster = savePoster("poster", $uploadDir);

            if ($id > 0) {
                $stmt = mysqli_prepare($conn, "SELECT poster FROM events1 WHERE event_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $oldEvent = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if (!$oldEvent) {
                    if ($newPoster) {
                        removePoster($newPoster, $uploadDir);
                    }
                    throw new Exception("Event not found.");
                }

                $poster = $newPoster ?? $oldEvent["poster"];

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE events1
                     SET title = ?, description = ?, event_date = ?,
                         event_time = ?, venue = ?, poster = ?
                     WHERE event_id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt, "ssssssi",
                    $title, $description, $date, $time, $venue, $poster, $id
                );

                if (!mysqli_stmt_execute($stmt)) {
                    if ($newPoster) {
                        removePoster($newPoster, $uploadDir);
                    }
                    throw new Exception("Could not update the event.");
                }

                mysqli_stmt_close($stmt);

                if ($newPoster) {
                    removePoster($oldEvent["poster"], $uploadDir);
                }

                $message = "Event updated successfully.";
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO events1
                     (title, description, event_date, event_time, venue, poster)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt, "ssssss",
                    $title, $description, $date, $time, $venue, $newPoster
                );

                if (!mysqli_stmt_execute($stmt)) {
                    if ($newPoster) {
                        removePoster($newPoster, $uploadDir);
                    }
                    throw new Exception("Could not add the event.");
                }

                mysqli_stmt_close($stmt);
                $message = "Event added successfully.";
            }
        } else {
            throw new Exception("Invalid action.");
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$editEvent = null;

if (isset($_GET["edit"])) {
    $editId = filter_var($_GET["edit"], FILTER_VALIDATE_INT);

    if ($editId && $editId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM events1 WHERE event_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $editId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $editEvent = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

$result = mysqli_query($conn, "SELECT * FROM events1 ORDER BY event_date DESC, event_time DESC");

if (!$result) {
    exit("Could not load events.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Event Management</title>
    <link rel="stylesheet" href="../CSS/events.css">
</head>
<body>

<header>
    <h1>StudentHub Event Management</h1>
    <a href="logout.php">Logout</a>
</header>

<main>
    <h2><?= $editEvent ? "Update Event" : "Add New Event" ?></h2>

    <?php if ($message): ?>
        <p class="success"><?= clean($message) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= clean($error) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token"
               value="<?= clean($_SESSION["event_csrf"]) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="event_id"
               value="<?= clean($editEvent["event_id"] ?? 0) ?>">

        <label for="title">Event Title</label>
        <input type="text" id="title" name="title" maxlength="150"
               required value="<?= clean($editEvent["title"] ?? "") ?>">

        <label for="description">Description</label>
        <textarea id="description" name="description" required><?= clean($editEvent["description"] ?? "") ?></textarea>

        <label for="event_date">Event Date</label>
        <input type="date" id="event_date" name="event_date" required
               value="<?= clean($editEvent["event_date"] ?? "") ?>">

        <label for="event_time">Event Time</label>
        <input type="time" id="event_time" name="event_time" required
               value="<?= clean(isset($editEvent["event_time"]) ? substr($editEvent["event_time"], 0, 5) : "") ?>">

        <label for="venue">Venue</label>
        <input type="text" id="venue" name="venue" maxlength="200"
               required value="<?= clean($editEvent["venue"] ?? "") ?>">

        <label for="poster">Event Poster (JPG, PNG or WebP; max 2 MB)</label>
        <input type="file" id="poster" name="poster"
               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

        <?php if (!empty($editEvent["poster"])): ?>
            <p>Current poster:</p>
            <img class="poster"
                 src="event_posters/<?= rawurlencode($editEvent["poster"]) ?>"
                 alt="Current event poster">
            <p>Choose a new poster only if you want to replace it.</p>
        <?php endif; ?>

        <button type="submit">
            <?= $editEvent ? "Update Event" : "Add Event" ?>
        </button>

        <?php if ($editEvent): ?>
            <a href="events.php">Cancel Edit</a>
        <?php endif; ?>
    </form>

    <h2>All Events</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($result) === 0): ?>
                <tr><td colspan="7">No events added yet.</td></tr>
            <?php else: ?>
                <?php while ($event = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= clean($event["event_id"]) ?></td>
                        <td>
                            <?php if ($event["poster"]): ?>
                                <img class="poster"
                                     src="event_posters/<?= rawurlencode($event["poster"]) ?>"
                                     alt="Event poster">
                            <?php else: ?>
                                No poster
                            <?php endif; ?>
                        </td>
                        <td><?= clean($event["title"]) ?></td>
                        <td><?= clean($event["event_date"]) ?></td>
                        <td><?= clean(substr($event["event_time"], 0, 5)) ?></td>
                        <td><?= clean($event["venue"]) ?></td>
                        <td>
                            <a href="events.php?edit=<?= (int)$event["event_id"] ?>">Edit</a>

                            <form method="POST" class="delete-form"
                                  onsubmit="return confirm('Are you sure you want to delete this event?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= clean($_SESSION["event_csrf"]) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id"
                                       value="<?= (int)$event["event_id"] ?>">
                                <button type="submit" class="delete-button">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
