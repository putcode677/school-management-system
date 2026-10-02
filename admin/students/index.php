<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get students
$sql = "SELECT
            students.id,
            students.student_number,
            students.date_of_birth,
            students.gender,
            students.phone,
            students.address,
            students.admission_date,
            users.full_name,
            users.email,
            users.status
        FROM students
        INNER JOIN users
            ON students.user_id = users.id
        ORDER BY students.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Students | School Management System</title>

    <link rel="stylesheet"
          href="../../assets/css/style.css">

</head>

<body>

<div class="container">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Students</h1>

            <p>
                Manage all students in the school.
            </p>

        </div>

        <a href="add.php" class="btn">
            + Add Student
        </a>

    </div>


    <!-- STUDENT COUNT -->

    <div class="card">

        <h2><?= count($students) ?></h2>

        <p>Total Students</p>

    </div>


    <!-- STUDENTS TABLE -->

    <?php if (count($students) > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>#</th>

                    <th>Student Number</th>

                    <th>Full Name</th>

                    <th>Email</th>

                    <th>Gender</th>

                    <th>Phone</th>

                    <th>Status</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

            <?php foreach ($students as $student): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($student['id']) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $student['student_number']
                        ) ?>
                    </td>


                    <td>
                        <strong>
                            <?= htmlspecialchars(
                                $student['full_name']
                            ) ?>
                        </strong>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $student['email']
                        ) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $student['gender'] ?? '-'
                        ) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $student['phone'] ?? '-'
                        ) ?>
                    </td>


                    <td>

                        <?php if ($student['status'] === 'active'): ?>

                            <span class="badge badge-success">
                                Active
                            </span>

                        <?php else: ?>

                            <span class="badge badge-danger">
                                <?= htmlspecialchars(
                                    ucfirst($student['status'])
                                ) ?>
                            </span>

                        <?php endif; ?>

                    </td>


                    <td>

                        <div class="actions">

                            <a
                                href="edit.php?id=<?= $student['id'] ?>"
                                class="btn"
                            >
                                Edit
                            </a>


                            <a
                                href="delete.php?id=<?= $student['id'] ?>"
                                class="btn btn-danger"
                                onclick="return confirm(
                                    'Are you sure you want to delete this student?'
                                );"
                            >
                                Delete
                            </a>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="card">

            <h2>No Students Found</h2>

            <p>
                There are currently no students registered
                in the system.
            </p>

            <br>

            <a href="add.php" class="btn">
                + Add First Student
            </a>

        </div>

    <?php endif; ?>

</div>

</body>

</html>