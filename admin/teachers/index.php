<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../auth/login.php");
    exit;
}

require_once "../../config/database.php";

$stmt = $pdo->query("
    SELECT
        teachers.id,
        teachers.employee_number,
        teachers.phone,
        teachers.specialization,
        teachers.hire_date,
        users.full_name,
        users.email
    FROM teachers
    INNER JOIN users ON teachers.user_id = users.id
    ORDER BY teachers.id DESC
");

$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Management</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            background: #e0e5ec;
            color: #333;
            min-height: 100vh;
            padding: 30px;
        }

        .container {
            max-width: 1250px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 30px;
            margin-bottom: 6px;
        }

        .header p {
            color: #777;
        }

        .buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 20px;
            border-radius: 15px;
            background: #e0e5ec;
            color: #444;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 7px 7px 14px #bec3c9,
                        -7px -7px 14px #ffffff;
            transition: .3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:active {
            box-shadow: inset 5px 5px 10px #bec3c9,
                        inset -5px -5px 10px #ffffff;
        }

        .summary {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 22px;
            margin-bottom: 25px;
            border-radius: 20px;
            box-shadow: 8px 8px 18px #bec3c9,
                        -8px -8px 18px #ffffff;
        }

        .summary-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 25px;
            box-shadow: inset 4px 4px 8px #bec3c9,
                        inset -4px -4px 8px #ffffff;
        }

        .summary h2 {
            font-size: 25px;
        }

        .summary p {
            color: #777;
            margin-top: 3px;
        }

        .table-container {
            padding: 25px;
            border-radius: 25px;
            overflow-x: auto;
            box-shadow: 10px 10px 20px #bec3c9,
                        -10px -10px 20px #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th {
            padding: 15px;
            text-align: left;
            color: #666;
            font-size: 14px;
        }

        td {
            padding: 17px 15px;
            border-top: 1px solid rgba(190, 195, 201, .35);
            font-size: 14px;
        }

        tr:hover td {
            background: rgba(255, 255, 255, .2);
        }

        .teacher-name {
            font-weight: 600;
        }

        .employee-number {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 10px;
            box-shadow: inset 3px 3px 6px #bec3c9,
                        inset -3px -3px 6px #ffffff;
            font-size: 12px;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            text-decoration: none;
            color: #555;
            box-shadow: 5px 5px 10px #bec3c9,
                        -5px -5px 10px #ffffff;
        }

        .action-btn:active {
            box-shadow: inset 4px 4px 8px #bec3c9,
                        inset -4px -4px 8px #ffffff;
        }

        .empty {
            text-align: center;
            padding: 60px 20px;
            color: #777;
        }

        .empty-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .empty h3 {
            margin-bottom: 8px;
            color: #555;
        }

        @media (max-width: 700px) {
            body {
                padding: 18px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .buttons {
                width: 100%;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">

        <div>
            <h1>Teachers Management</h1>
            <p>Manage school teachers and their information.</p>
        </div>

        <div class="buttons">

            <a href="../../dashboard/admin.php" class="btn">
                🏠 Dashboard
            </a>

            <a href="add.php" class="btn">
                ➕ Add Teacher
            </a>

        </div>

    </div>


    <div class="summary">

        <div class="summary-icon">
            👨‍🏫
        </div>

        <div>
            <h2><?php echo count($teachers); ?></h2>
            <p>Total Teachers</p>
        </div>

    </div>


    <div class="table-container">

        <?php if (count($teachers) > 0): ?>

            <table>

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Teacher</th>
                        <th>Employee Number</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Specialization</th>
                        <th>Hire Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($teachers as $index => $teacher): ?>

                        <tr>

                            <td>
                                <?php echo $index + 1; ?>
                            </td>

                            <td>
                                <div class="teacher-name">
                                    <?php echo htmlspecialchars($teacher["full_name"]); ?>
                                </div>
                            </td>

                            <td>
                                <span class="employee-number">
                                    <?php echo htmlspecialchars($teacher["employee_number"]); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($teacher["email"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($teacher["phone"] ?? "-"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($teacher["specialization"] ?? "-"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($teacher["hire_date"] ?? "-"); ?>
                            </td>

                            <td>

                                <div class="actions">

                                    <a href="edit.php?id=<?php echo $teacher["id"]; ?>"
                                       class="action-btn"
                                       title="Edit">
                                        ✏️
                                    </a>

                                    <a href="delete.php?id=<?php echo $teacher["id"]; ?>"
                                       class="action-btn"
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this teacher?');">
                                        🗑️
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">
                    👨‍🏫
                </div>

                <h3>No Teachers Found</h3>

                <p>
                    There are currently no teachers registered in the system.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
