<?php

session_start();

require_once "../../config/database.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit;
}

// Only admin
if ($_SESSION["role"] !== "admin") {
    header("Location: ../../auth/login.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $subject_code = trim($_POST["subject_code"] ?? "");
    $subject_name = trim($_POST["subject_name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    // Validation
    if ($subject_code === "" || $subject_name === "") {

        $error = "Subject code and subject name are required.";

    } else {

        // Check if subject code already exists
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
                $description
            ]);

            $success = "Subject added successfully.";

            // Clear form values
            $subject_code = "";
            $subject_name = "";
            $description = "";
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
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 30px;
        }

        .container {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 25px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #007bff;
        }

        .btn {
            border: none;
            background: #007bff;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .btn:hover {
            background: #0056b3;
        }

        .back {
            display: inline-block;
            margin-left: 10px;
            text-decoration: none;
            color: #555;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Add New Subject</h1>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <?php if ($success !== ""): ?>

        <div class="success">
            <?php echo htmlspecialchars($success); ?>
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
                value="<?php echo htmlspecialchars($subject_code ?? ""); ?>"
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
                value="<?php echo htmlspecialchars($subject_name ?? ""); ?>"
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
            ><?php echo htmlspecialchars($description ?? ""); ?></textarea>

        </div>

        <button type="submit" class="btn">
            Add Subject
        </button>

        <a
            href="index.php"
            class="back"
        >
            Cancel
        </a>

    </form>

</div>

</body>

</html>