<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Remove assignment if requested
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    if ($delete_id > 0) {
        $delStmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE id = ?");
        $delStmt->execute([$delete_id]);
        header("Location: index.php?deleted=1");
        exit();
    }
}

// Fetch all teacher-subject assignments
$assignments = $pdo->query("
    SELECT
        ts.id,
        ts.created_at,
        t.employee_number,
        u.full_name AS teacher_name,
        s.subject_name,
        s.subject_code
    FROM teacher_subjects ts
    INNER JOIN teachers t ON t.id = ts.teacher_id
    INNER JOIN users u ON u.id = t.user_id
    INNER JOIN subjects s ON s.id = ts.subject_id
    ORDER BY s.subject_name ASC, u.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Subject Assignments | School Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="page-header">
        <div>
            <h1>Teacher Subject Assignments</h1>
            <p>Manage which teachers teach which subjects.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="../../dashboard/admin.php" class="btn btn-secondary">← Dashboard</a>
            <a href="add.php" class="btn">+ Assign Teacher to Subject</a>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="success">✅ Subject assignment removed successfully.</div>
    <?php endif; ?>

    <div class="card">
        <h2><?= count($assignments) ?> Total Subject Assignments</h2>

        <?php if (empty($assignments)): ?>
            <p style="margin-top:15px; color:#627d98;">No teachers assigned to any subjects yet.</p>
            <br>
            <a href="add.php" class="btn">+ Assign First Teacher</a>
        <?php else: ?>
            <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                <thead>
                    <tr style="text-align:left; border-bottom: 2px solid #b8c9dc;">
                        <th style="padding:12px;">#</th>
                        <th style="padding:12px;">Subject Name</th>
                        <th style="padding:12px;">Subject Code</th>
                        <th style="padding:12px;">Teacher Name</th>
                        <th style="padding:12px;">Employee ID</th>
                        <th style="padding:12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $index => $row): ?>
                        <tr style="border-bottom: 1px solid #dbeafe;">
                            <td style="padding:12px;"><?= $index + 1 ?></td>
                            <td style="padding:12px; font-weight:600;"><?= htmlspecialchars($row['subject_name']) ?></td>
                            <td style="padding:12px;"><span class="badge badge-success"><?= htmlspecialchars($row['subject_code']) ?></span></td>
                            <td style="padding:12px;"><strong><?= htmlspecialchars($row['teacher_name']) ?></strong></td>
                            <td style="padding:12px;"><span class="badge badge-success"><?= htmlspecialchars($row['employee_number']) ?></span></td>
                            <td style="padding:12px;">
                                <a href="index.php?delete=<?= (int)$row['id'] ?>" class="btn btn-danger" style="padding:6px 12px; font-size:12px;"
                                   onclick="return confirm('Remove this teacher from this subject?');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>

