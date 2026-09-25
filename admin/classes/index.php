```php
<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get classes
$sql = "SELECT
            id,
            class_name,
            class_code,
            description,
            created_at
        FROM classes
        ORDER BY id DESC";

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
                Manage all classes in the school.
            </p>

        </div>

        <a href="add.php" class="btn">
            + Add Class
        </a>

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

                    <th>Description</th>

                    <th>Created At</th>

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

                        <?= htmlspecialchars(
                            $class['class_code']
                        ) ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            $class['description'] ?? '-'
                        ) ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            date(
                                'd M Y',
                                strtotime($class['created_at'])
                            )
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
```
