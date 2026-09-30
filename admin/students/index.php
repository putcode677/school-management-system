<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit("Forbidden");
}

require_once "../../config/database.php";

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

<div class="page-header">
    <div>
        <h1>Students</h1>
        <p>Manage all students in the school.</p>
    </div>
    <a href="/school-management-system/admin/students/add.php" class="btn">+ Add Student</a>
</div>

<div class="content-card" style="margin-bottom:22px;">
    <h2><?= count($students) ?></h2>
    <p>Total Students</p>
</div>

<?php if (count($students) > 0): ?>

    <div class="content-card">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="text-align:left; border-bottom:2px solid #b8c9dc;">
                    <th style="padding:12px;">#</th>
                    <th style="padding:12px;">Student Number</th>
                    <th style="padding:12px;">Full Name</th>
                    <th style="padding:12px;">Email</th>
                    <th style="padding:12px;">Gender</th>
                    <th style="padding:12px;">Phone</th>
                    <th style="padding:12px;">Status</th>
                    <th style="padding:12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr style="border-bottom:1px solid #dbeafe;">
                    <td style="padding:12px;"><?= htmlspecialchars($student['id']) ?></td>
                    <td style="padding:12px;"><?= htmlspecialchars($student['student_number']) ?></td>
                    <td style="padding:12px;"><strong><?= htmlspecialchars($student['full_name']) ?></strong></td>
                    <td style="padding:12px;"><?= htmlspecialchars($student['email']) ?></td>
                    <td style="padding:12px;"><?= htmlspecialchars($student['gender'] ?? '-') ?></td>
                    <td style="padding:12px;"><?= htmlspecialchars($student['phone'] ?? '-') ?></td>
                    <td style="padding:12px;">
                        <?php if ($student['status'] === 'active'): ?>
                            <span style="color:#166534;">● Active</span>
                        <?php else: ?>
                            <span style="color:#991b1b;">● <?= htmlspecialchars(ucfirst($student['status'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;">
                        <a href="/school-management-system/admin/students/edit.php?id=<?= $student['id'] ?>">Edit</a> |
                        <a href="/school-management-system/admin/students/delete.php?id=<?= $student['id'] ?>"
                           onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>

    <div class="content-card">
        <h2>No Students Found</h2>
        <p>There are currently no students registered in the system.</p>
        <br>
        <a href="/school-management-system/admin/students/add.php" class="btn">+ Add First Student</a>
    </div>

<?php endif; ?>