<?php
session_start();

require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get subjects
try {

    $stmt = $pdo->prepare(
        "SELECT
            id,
            subject_name,
            subject_code,
            description,
            created_at
         FROM subjects
         ORDER BY id DESC"
    );

    $stmt->execute();

    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
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

    <title>Subjects Management</title>

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
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: #dbeafe;
            padding: 30px;
            border-radius: 25px;

            box-shadow:
                12px 12px 25px #b8c9dc,
                -12px -12px 25px #ffffff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .header p {
            margin: 7px 0 0;
            opacity: 0.75;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-block;
            border: none;
            text-decoration: none;
            text-align: center;
            cursor: pointer;

            padding: 13px 18px;
            border-radius: 14px;

            font-size: 14px;
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

        .btn-dashboard {
            color: white;
            background: #174a7e;
        }

        .btn-danger {
            color: #b42318;
        }

        .summary {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .count-box {
            min-width: 130px;
            padding: 18px;
            text-align: center;
            border-radius: 18px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        .count-box strong {
            display: block;
            font-size: 28px;
            color: #1769aa;
        }

        .count-box span {
            font-size: 13px;
            font-weight: bold;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 20px;

            box-shadow:
                inset 5px 5px 12px #b8c9dc,
                inset -5px -5px 12px #ffffff;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 800px;
        }

        th,
        td {
            padding: 16px;
            text-align: left;
        }

        th {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #174a7e;
        }

        tbody tr {
            transition: 0.2s ease;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        td {
            border-top: 1px solid rgba(184, 201, 220, 0.35);
            font-size: 14px;
        }

        .subject-name {
            font-weight: bold;
            color: #1769aa;
        }

        .subject-code {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 10px;
            font-weight: bold;

            box-shadow:
                inset 3px 3px 7px #b8c9dc,
                inset -3px -3px 7px #ffffff;
        }

        .actions {
            display: flex;
            gap: 10px;
            white-space: nowrap;
        }

        .empty {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .empty h2 {
            margin: 0 0 8px;
        }

        .empty p {
            margin: 0 0 25px;
            opacity: 0.7;
        }

        @media (max-width: 700px) {

            .container {
                margin: 20px auto;
                padding: 15px;
            }

            .card {
                padding: 20px;
            }

            .header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .summary {
                margin-bottom: 20px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card">

        <div class="header">

            <div>
                <h1>📚 Subjects Management</h1>
                <p>Manage school subjects from this section.</p>
            </div>

            <div class="header-actions">

                <a
                    href="../../dashboard/admin.php"
                    class="btn btn-dashboard"
                >
                    ← Dashboard
                </a>

                <a
                    href="add.php"
                    class="btn btn-primary"
                >
                    + Add Subject
                </a>

            </div>

        </div>


        <div class="summary">

            <div class="count-box">

                <strong><?= count($subjects) ?></strong>

                <span>Total Subjects</span>

            </div>

        </div>


        <?php if (count($subjects) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>#</th>
                            <th>Subject Name</th>
                            <th>Subject Code</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($subjects as $index => $subject): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>

                            <td>

                                <span class="subject-name">
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </span>

                            </td>

                            <td>

                                <span class="subject-code">
                                    <?= htmlspecialchars($subject['subject_code']) ?>
                                </span>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $subject['description'] ?? '—'
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $subject['created_at']
                                ) ?>

                            </td>

                            <td>

                                <div class="actions">

                                    <a
                                        href="edit.php?id=<?= (int) $subject['id'] ?>"
                                        class="btn"
                                    >
                                        ✏️ Edit
                                    </a>

                                    <a
                                        href="delete.php?id=<?= (int) $subject['id'] ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this subject?');"
                                    >
                                        🗑️ Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">
                    📚
                </div>

                <h2>No Subjects Found</h2>

                <p>
                    Start by adding your first school subject.
                </p>

                <a
                    href="add.php"
                    class="btn btn-primary"
                >
                    + Add First Subject
                </a>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>

