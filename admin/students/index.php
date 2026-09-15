<?php
session_start();
require_once "../../config/database.php";

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get all students
$sql = "SELECT 
            students.id,
            students.registration_number,
            students.first_name,
            students.middle_name,
            students.last_name,
            students.gender,
            students.phone,
            students.date_of_birth
        FROM students
        ORDER BY students.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<div class="container">

    <h1>Manage Students</h1>

    <a href="add.php">+ Add New Student</a>

    <br><br>

    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>#</th>
                <th>Registration Number</th>
                <th>Full Name</th>
                <th>Gender</th>
                <th>Phone</th>
                <th>Date of Birth</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php if (count($students) > 0): ?>

            <?php foreach ($students as $student): ?>

                <tr>
                    <td><?= htmlspecialchars($student['id']) ?></td>

                    <td>
                        <?= htmlspecialchars($student['registration_number']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $student['first_name'] . ' ' .
                            $student['middle_name'] . ' ' .
                            $student['last_name']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($student['gender']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($student['phone']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($student['date_of_birth']) ?>
                    </td>

                    <td>
                        <a href="edit.php?id=<?= $student['id'] ?>">
                            Edit
                        </a>

                        |

                        <a href="delete.php?id=<?= $student['id'] ?>"
                           onclick="return confirm('Are you sure you want to delete this student?');">
                            Delete
                        </a>
                    </td>
                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="7">
                    No students found.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>
    </table>

</div>

</body>
</html>