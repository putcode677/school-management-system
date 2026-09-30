<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$parents = $pdo->query("
    SELECT
        parents.id,
        users.full_name,
        users.email,
        parents.phone,
        parents.occupation,
        STRING_AGG(student_users.full_name, ', ') AS children_names
    FROM parents
    INNER JOIN users ON parents.user_id = users.id
    LEFT JOIN student_parents ON student_parents.parent_id = parents.id
    LEFT JOIN students ON students.id = student_parents.student_id
    LEFT JOIN users AS student_users ON student_users.id = students.user_id
    WHERE users.status = 'active'
    GROUP BY parents.id, users.full_name, users.email, parents.phone, parents.occupation
    ORDER BY users.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parents | School Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="page-header">
        <div>
            <h1>Parents</h1>
            <p>Manage parent/guardian accounts and their children.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="link.php" class="btn btn-secondary">🔗 Link Parent to Student</a>
            <a href="add.php" class="btn">➕ Add Parent</a>
        </div>
    </div>

    <div class="card">

        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align:left; border-bottom: 2px solid #b8c9dc;">
                    <th style="padding:12px;">Full Name</th>
                    <th style="padding:12px;">Email</th>
                    <th style="padding:12px;">Phone</th>
                    <th style="padding:12px;">Occupation</th>
                    <th style="padding:12px;">Children</th>
                    <th style="padding:12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($parents)): ?>
                    <tr>
                        <td colspan="6" style="padding:20px; text-align:center; color:#627d98;">
                            No parents found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($parents as $parent): ?>
                        <tr style="border-bottom: 1px solid #dbeafe;">
                            <td style="padding:12px;"><?= htmlspecialchars($parent['full_name']) ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars($parent['email']) ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars($parent['phone'] ?? '-') ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars($parent['occupation'] ?? '-') ?></td>
                            <td style="padding:12px;">
                                <?= $parent['children_names']
                                    ? htmlspecialchars($parent['children_names'])
                                    : '<span style="color:#a0aec0;">No children linked</span>' ?>
                            </td>
                            <td style="padding:12px;">
                                <a href="edit.php?id=<?= (int)$parent['id'] ?>">Edit</a> |
                                <a href="delete.php?id=<?= (int)$parent['id'] ?>"
                                   onclick="return confirm('Are you sure you want to delete this parent?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <div style="margin-top:20px;">
        <a href="../../dashboard/admin.php" class="btn btn-secondary">← Dashboard</a>
    </div>

</div>

</body>
</html>