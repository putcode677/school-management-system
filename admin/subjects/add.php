<?php

session_start();

require_once "../../config/database.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Only admin
if ($_SESSION["role"] !== "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

$error = "";
$success = "";

$subject_code = "";
$subject_name = "";
$description = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $subject_code = trim($_POST["subject_code"] ?? "");
    $subject_name = trim($_POST["subject_name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    // Validation
    if ($subject_code === "" || $subject_name === "") {

        $error = "Subject code and subject name are required.";

    } else {

        try {

            // Check duplicate subject code
            $check = $pdo->prepare("
                SELECT id
                FROM subjects
                WHERE subject_code = ?
            ");

            $check->execute([$subject_code]);

            if ($check->fetch()) {

                $error = "This subject code already exists.";

            } else {

                // Insert subject
                $stmt = $pdo->prepare("
                    INSERT INTO subjects
                    (subject_code, subject_name, description)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $subject_code,
                    $subject_name,
                    $description !== "" ? $description : null
                ]);

                $success = "Subject added successfully.";

                // Clear form
                $subject_code = "";
                $subject_name = "";
                $description = "";
            }

        } catch (PDOException $e) {

            $error = "Database Error: " . $e->getMessage();
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

    <title>Add Subject</title>

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

        .header-content h1 {
            margin: 0;
            font-size: 28px;
        }

        .header-content p {
            margin: 7px 0 0;
            font-size: 14px;
            opacity: 0.7;
        }

        .back-btn {
            text-decoration: none;
            color: #243b53;
            background: #dbeafe;
            padding: 12px 18px;
            border-radius: 14px;
            font-weight: bold;
            white-space: nowrap;

            box-shadow:
                6px 6px 12px #b8c9dc,
                -6px -6px 12px #ffffff;
        }

        .back-btn:active {
            box-shadow:
                inset 4px 4px 8px #b8c9dc,
                inset -4px -4px 8px #ffffff;
        }

        .message {
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 22px;
            font-weight: bold;
        }

        .error {
            color: #b42318;
            background: #fbe9e7;

            box-shadow:
                5px 5px 10px #b8c9dc,
                -5px -5px 10px #ffffff;
        }

        .success {
            color: #166534;
            background: #dcfce7;

            box-shadow:
                5px 5px 10px #b8c9dc,
                -5px -5px 10px #ffffff;
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

        input::placeholder,
        textarea::placeholder {
            color: #6b7f93;
        }

        input:focus,
        textarea:focus {
            box-shadow:
                inset 3px 3px 7px #b8c9dc,
                inset -3px -3px 7px #ffffff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
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

        .btn:active {
            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        .btn-primary {
            color: white;
            background: #1769aa;

            box-shadow:
                7px 7px 14px #9bb8d4,
                -7px -7px 14px #ffffff;
        }

        .btn-primary:active {
            box-shadow:
                inset 5px 5px 10px #0f4f80,
                inset -5px -5px 10px #3d8ac2;
        }

        .btn-dashboard {
            color: white;
            background: #174a7e;
        }

        @media (max-width: 600px) {

            .container {
                margin: 20px auto;
                padding: 15px;
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

            <div class="header-content">

                <h1>📚 Add New Subject</h1>

                <p>
                    Create a new subject for the school.
                </p>

            </div>

            <a
                href="index.php"
                class="back-btn"
            >
                ← Subjects
            </a>

        </div>


        <?php if ($error !== ""): ?>

            <div class="message error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="message success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label for="subject_code">
                    Subject Code
                </label>

                <input
                    type="text"
                    id="subject_code"
                    name="subject_code"
                    placeholder="Example: CS101"
                    value="<?= htmlspecialchars($subject_code) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="subject_name">
                    Subject Name
                </label>

                <input
                    type="text"
                    id="subject_name"
                    name="subject_name"
                    placeholder="Example: Computer Science"
                    value="<?= htmlspecialchars($subject_name) ?>"
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
                    placeholder="Enter subject description..."
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
                    ➕ Add Subject
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>

