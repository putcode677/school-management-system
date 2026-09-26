<?php

session_start();

require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get subject ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Variables
$subject_name = "";
$subject_code = "";
$description = "";

$errors = [];
$success = "";

// Get subject
try {

    $stmt = $pdo->prepare(
        "SELECT id, subject_name, subject_code, description
         FROM subjects
         WHERE id = ?"
    );

    $stmt->execute([$id]);

    $subject = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subject) {
        header("Location: index.php");
        exit();
    }

    $subject_name = $subject['subject_name'];
    $subject_code = $subject['subject_code'];
    $description = $subject['description'] ?? "";

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}


// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subject_name = trim($_POST['subject_name'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if ($subject_name === '') {
        $errors[] = "Subject name is required.";
    }

    if ($subject_code === '') {
        $errors[] = "Subject code is required.";
    }

    // Check duplicate subject code
    if (empty($errors)) {

        try {

            $check = $pdo->prepare(
                "SELECT id
                 FROM subjects
                 WHERE subject_code = ?
                 AND id != ?"
            );

            $check->execute([
                $subject_code,
                $id
            ]);

            if ($check->fetch()) {
                $errors[] = "Subject code already exists.";
            }

        } catch (PDOException $e) {

            $errors[] = "Database Error: " . $e->getMessage();
        }
    }


    // Update subject
    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                "UPDATE subjects
                 SET subject_name = ?,
                     subject_code = ?,
                     description = ?
                 WHERE id = ?"
            );

            $stmt->execute([
                $subject_name,
                $subject_code,
                $description !== '' ? $description : null,
                $id
            ]);

            $success = "Subject updated successfully.";

        } catch (PDOException $e) {

            $errors[] = "Database Error: " . $e->getMessage();
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

    <title>Edit Subject</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;

            min-height: 100vh;

            background: #dbeafe;

            color: #243b53;

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 30px 15px;
        }

        .container {
            width: 100%;

            max-width: 650px;

            background: #dbeafe;

            padding: 35px;

            border-radius: 28px;

            box-shadow:
                14px 14px 28px #b8c9dc,
                -14px -14px 28px #ffffff;
        }

        .header {
            text-align: center;

            margin-bottom: 30px;
        }

        .icon {
            width: 75px;

            height: 75px;

            margin: 0 auto 18px;

            border-radius: 22px;

            display: flex;

            justify-content: center;

            align-items: center;

            font-size: 35px;

            background: #dbeafe;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        h1 {
            font-size: 28px;

            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;

            font-size: 14px;
        }

        .message {
            padding: 14px 18px;

            border-radius: 14px;

            margin-bottom: 20px;

            font-size: 14px;

            font-weight: bold;
        }

        .error {
            color: #991b1b;

            background: #fee2e2;

            box-shadow:
                inset 4px 4px 8px #d8b4b4,
                inset -4px -4px 8px #ffffff;
        }

        .success {
            color: #166534;

            background: #dcfce7;

            box-shadow:
                inset 4px 4px 8px #b7d7c0,
                inset -4px -4px 8px #ffffff;
        }

        .error ul {
            padding-left: 20px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;

            font-size: 14px;

            font-weight: bold;

            margin-bottom: 9px;

            color: #243b53;
        }

        .required {
            color: #1769aa;
        }

        input,
        textarea {
            width: 100%;

            border: none;

            outline: none;

            background: #dbeafe;

            color: #243b53;

            font-size: 15px;

            padding: 15px 17px;

            border-radius: 15px;

            box-shadow:
                inset 6px 6px 12px #b8c9dc,
                inset -6px -6px 12px #ffffff;

            transition: 0.2s ease;
        }

        input:focus,
        textarea:focus {
            box-shadow:
                inset 3px 3px 7px #b8c9dc,
                inset -3px -3px 7px #ffffff;
        }

        textarea {
            min-height: 130px;

            resize: vertical;

            line-height: 1.5;
        }

        .buttons {
            display: flex;

            gap: 15px;

            margin-top: 30px;
        }

        .btn {
            flex: 1;

            border: none;

            text-decoration: none;

            text-align: center;

            padding: 15px 20px;

            border-radius: 15px;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-cancel {
            color: #243b53;

            background: #dbeafe;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        .btn-dashboard {
            color: #1769aa;

            background: #dbeafe;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        .btn-update {
            color: white;

            background: #1769aa;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        .btn-update:hover {
            background: #174a7e;
        }

        .back-link {
            display: block;

            text-align: center;

            margin-top: 25px;

            color: #1769aa;

            text-decoration: none;

            font-size: 14px;

            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {

            .container {
                padding: 25px 20px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

    <div class="container">

        <div class="header">

            <div class="icon">
                ✏️
            </div>

            <h1>Edit Subject</h1>

            <p class="subtitle">
                Update subject information
            </p>

        </div>


        <?php if (!empty($errors)): ?>

            <div class="message error">

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= htmlspecialchars($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="message success">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <form method="POST">

            <!-- Subject Name -->

            <div class="form-group">

                <label for="subject_name">
                    Subject Name
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="subject_name"
                    name="subject_name"
                    value="<?= htmlspecialchars($subject_name) ?>"
                    placeholder="Enter subject name"
                    required
                >

            </div>


            <!-- Subject Code -->

            <div class="form-group">

                <label for="subject_code">
                    Subject Code
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="subject_code"
                    name="subject_code"
                    value="<?= htmlspecialchars($subject_code) ?>"
                    placeholder="Enter subject code"
                    required
                >

            </div>


            <!-- Description -->

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


            <!-- Buttons -->

            <div class="buttons">

                <a href="index.php"
                   class="btn btn-cancel">
                    ← Cancel
                </a>

                <a href="../../dashboard/admin.php"
                   class="btn btn-dashboard">
                    🏠 Dashboard
                </a>

                <button type="submit"
                        class="btn btn-update">
                    ✏️ Update Subject
                </button>

            </div>

        </form>


        <a href="index.php"
           class="back-link">
            ← Back to Subjects
        </a>

    </div>

</body>

</html>

