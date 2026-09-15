<?php

session_start();

require_once "../../config/database.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit;
}

// Only admin can manage subjects
if ($_SESSION["role"] !== "admin") {
    header("Location: ../../auth/login.php");
    exit;
}

// Fetch subjects
$stmt = $pdo->query("
    SELECT *
    FROM subjects
    ORDER BY id DESC
");

$subjects = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Subjects</title>

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
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #333;
        }

        .add-btn {
            text-decoration: none;
            background: #007bff;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
        }

        .add-btn:hover {
            background: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #343a40;
            color: white;
        }

        tr:hover {
            background: #f5f5f5;
        }

        .edit {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
        }

        .delete {
            color: #dc3545;
            text-decoration: none;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #555;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <h1>Manage Subjects</h1>

        <a href="add.php" class="add-btn">
            + Add Subject
        </a>

    </div>

    <?php if (count($subjects) > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>#</th>

                    <th>Subject Code</th>

                    <th>Subject Name</th>

                    <th>Description</th>

                    <th>Created At</th>

                    <th>Actions</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($subjects as $subject): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($subject["id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($subject["subject_code"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($subject["subject_name"]); ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $subject["description"] ?? ""
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($subject["created_at"]); ?>
                        </td>

                        <td>

                            <a
                                href="edit.php?id=<?php echo $subject["id"]; ?>"
                                class="edit"
                            >
                                Edit
                            </a>

                            <a
                                href="delete.php?id=<?php echo $subject["id"]; ?>"
                                class="delete"
                                onclick="return confirm('Are you sure you want to delete this subject?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="empty">
            No subjects found.
        </div>

    <?php endif; ?>

    <a
        href="../../dashboard/admin.php"
        class="back"
    >
        ← Back to Admin Dashboard
    </a>

</div>

</body>

</html>