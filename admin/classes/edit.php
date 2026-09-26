<?php
session_start();

require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get class ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

$message = "";
$class_name = "";
$class_code = "";
$description = "";

// Get existing class
try {
    $stmt = $pdo->prepare(
        "SELECT id, class_name, class_code, description
         FROM classes
         WHERE id = ?"
    );

    $stmt->execute([$id]);

    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        header("Location: index.php");
        exit();
    }

    $class_name = $class['class_name'];
    $class_code = $class['class_code'];
    $description = $class['description'] ?? '';

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $class_name = trim($_POST['class_name'] ?? '');
    $class_code = trim($_POST['class_code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate required fields
    if ($class_name === '' || $class_code === '') {

        $message = "Class Name and Class Code are required.";

    } else {

        try {

            // Check if another class uses this code
            $check = $pdo->prepare(
                "SELECT id
                 FROM classes
                 WHERE class_code = ?
                 AND id != ?"
            );

            $check->execute([$class_code, $id]);

            if ($check->fetch(PDO::FETCH_ASSOC)) {

                $message = "This Class Code already exists.";

            } else {

                // Update class
                $update = $pdo->prepare(
                    "UPDATE classes
                     SET class_name = ?,
                         class_code = ?,
                         description = ?
                     WHERE id = ?"
                );

                $update->execute([
                    $class_name,
                    $class_code,
                    $description !== '' ? $description : null,
                    $id
                ]);

                // Return to classes list
                header("Location: index.php");
                exit();
            }

        } catch (PDOException $e) {

            $message = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Class</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #dbeafe;
            color: #243b53;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: 50px auto;
            padding: 20px;
        }

        .card {
            background: #dbeafe;
            padding: 35px;
            border-radius: 25px;

            box-shadow:
                12px 12px 25px #b8c9dc,
                -12px -12px 25px #ffffff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 27px;
        }

        .back-btn {
            text-decoration: none;
            color: #243b53;
            background: #dbeafe;
            padding: 12px 18px;
            border-radius: 14px;
            font-weight: bold;

            box-shadow:
                6px 6px 12px #b8c9dc,
                -6px -6px 12px #ffffff;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-weight: bold;
        }

        input,
        textarea {
            width: 100%;
            border: none;
            outline: none;
            background: #dbeafe;
            color: #243b53;

            padding: 15px;
            border-radius: 15px;
            font-size: 15px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            box-shadow:
                inset 3px 3px 7px #b8c9dc,
                inset -3px -3px 7px #ffffff;
        }

        .message {
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 14px;
            color: #b42318;
            background: #fbe9e7;

            box-shadow:
                5px 5px 10px #b8c9dc,
                -5px -5px 10px #ffffff;
        }

        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            border: none;
            text-decoration: none;
            text-align: center;
            cursor: pointer;

            padding: 15px;
            border-radius: 15px;

            font-size: 15px;
            font-weight: bold;

            color: #243b53;
            background: #dbeafe;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        .btn-primary {
            color: white;
            background: #1769aa;

            box-shadow:
                7px 7px 14px #9bb8d4,
                -7px -7px 14px #ffffff;
        }

        .btn-dashboard {
            color: white;
            background: #174a7e;
        }

        .btn:active {
            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        @media (max-width: 600px) {

            .container {
                margin: 20px auto;
            }

            .card {
                padding: 25px;
            }

            .header {
                flex-direction: column;
                align-items: stretch;
            }

            .actions {
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card">

        <div class="header">

            <h1>✏️ Edit Class</h1>

            <a
                href="index.php"
                class="back-btn"
            >
                ← Classes
            </a>

        </div>

        <?php if ($message !== ''): ?>

            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label for="class_name">
                    Class Name
                </label>

                <input
                    type="text"
                    id="class_name"
                    name="class_name"
                    value="<?= htmlspecialchars($class_name) ?>"
                    placeholder="e.g. Form One"
                    required
                >

            </div>


            <div class="form-group">

                <label for="class_code">
                    Class Code
                </label>

                <input
                    type="text"
                    id="class_code"
                    name="class_code"
                    value="<?= htmlspecialchars($class_code) ?>"
                    placeholder="e.g. F1"
                    required
                >

            </div>


            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter class description..."
                ><?= htmlspecialchars($description) ?></textarea>

            </div>


            <div class="actions">

                <a
                    href="index.php"
                    class="btn"
                >
                    Cancel
                </a>

                <a
                    href="../../dashboard/admin.php"
                    class="btn btn-dashboard"
                >
                    ← Dashboard
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    💾 Update Class
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>

