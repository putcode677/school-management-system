<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get classes with student and teacher counts
$sql = "SELECT
            classes.id,
            classes.class_name,
            classes.class_code,
            classes.description,
            classes.created_at,
            COUNT(DISTINCT student_classes.student_id) AS student_count,
            COUNT(DISTINCT teacher_classes.teacher_id) AS teacher_count
        FROM classes
        LEFT JOIN student_classes ON student_classes.class_id = classes.id
        LEFT JOIN teacher_classes ON teacher_classes.class_id = classes.id
        GROUP BY classes.id, classes.class_name, classes.class_code, classes.description, classes.created_at
        ORDER BY classes.class_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Classes | School Management System</title>

    <link rel="stylesheet"
          href="../../assets/css/style.css">

</head>

<body>

<div class="container">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Classes</h1>

            <p>
                Manage all classes and classroom assignments in the school.
            </p>

        </div>

        <div style="display:flex; gap:10px;">
            <a href="../../dashboard/admin.php" class="btn btn-secondary">
                ← Dashboard
            </a>
            <a href="add.php" class="btn">
                + Add Class
            </a>
        </div>

    </div>


    <!-- CLASS COUNT -->

    <div class="card">

        <h2><?= count($classes) ?></h2>

        <p>Total Classes</p>

    </div>


    <!-- CLASSES TABLE -->

    <?php if (count($classes) > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>#</th>

                    <th>Class Name</th>

                    <th>Class Code</th>

                    <th>Students</th>

                    <th>Teachers</th>

                    <th>Description</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>

            <?php foreach ($classes as $class): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($class['id']) ?>
                    </td>


                    <td>

                        <strong>
                            <?= htmlspecialchars(
                                $class['class_name']
                            ) ?>
                        </strong>

                    </td>


                    <td>

                        <span class="badge badge-success">
                            <?= htmlspecialchars(
                                $class['class_code']
                            ) ?>
                        </span>

                    </td>

                    <td>
                        <?= (int) $class['student_count'] ?> students
                    </td>

                    <td>
                        <?= (int) $class['teacher_count'] ?> teachers
                    </td>

                    <td>

                        <?= htmlspecialchars(
                            $class['description'] ?? '-'
                        ) ?>

                    </td>


                    <td>

                        <div class="actions">

                            <a
                                href="edit.php?id=<?= $class['id'] ?>"
                                class="btn"
                            >
                                Edit
                            </a>


                            <a
                                href="delete.php?id=<?= $class['id'] ?>"
                                class="btn btn-danger"
                                onclick="return confirm(
                                    'Are you sure you want to delete this class?'
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

            <h2>No Classes Found</h2>

            <p>
                There are currently no classes registered
                in the system.
            </p>

            <br>

            <a href="add.php" class="btn">
                + Add First Class
            </a>

        </div>

    <?php endif; ?>

</div>

</body>

</html>
