<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../auth/login.php");
    exit;
}

$teachers = $pdo->query("
    SELECT 
        teachers.id,
        teachers.employee_number,
        users.full_name
    FROM teachers
    INNER JOIN users ON teachers.user_id = users.id
    WHERE users.status = 'active'
    ORDER BY users.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$subjects = $pdo->query("
    SELECT id, subject_name, subject_code
    FROM subjects
    ORDER BY subject_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $teacher_id = (int)($_POST["teacher_id"] ?? 0);
    $subject_id = (int)($_POST["subject_id"] ?? 0);

    if ($teacher_id <= 0 || $subject_id <= 0) {

        $message = "Please select both teacher and subject.";
        $message_type = "error";

    } else {

        try {

            // Check if assignment already exists
            $check = $pdo->prepare("
                SELECT id
                FROM teacher_subjects
                WHERE teacher_id = ? AND subject_id = ?
            ");

            $check->execute([$teacher_id, $subject_id]);

            if ($check->fetch()) {

                $message = "This teacher is already assigned to this subject.";
                $message_type = "error";

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO teacher_subjects (teacher_id, subject_id)
                    VALUES (?, ?)
                ");

                $stmt->execute([$teacher_id, $subject_id]);

                $message = "Teacher assigned to subject successfully.";
                $message_type = "success";
            }

        } catch (PDOException $e) {

            $message = "Database error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Teacher to Subject - School Management System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #dbeafe;
            color: #243b53;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .container {
            width: 100%;
            max-width: 650px;
        }

        .card {
            background: #dbeafe;
            padding: 40px;
            border-radius: 28px;
            box-shadow: 12px 12px 25px #b8c9dc, -12px -12px 25px #ffffff;
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            font-size: 38px;
            box-shadow: 8px 8px 16px #b8c9dc, -8px -8px 16px #ffffff;
        }

        h1 {
            text-align: center;
            color: #174a7e;
            margin-bottom: 8px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #607d98;
            margin-bottom: 30px;
        }

        .message {
            padding: 15px 18px;
            border-radius: 15px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .success {
            color: #166534;
            background: #dcfce7;
        }

        .error {
            color: #991b1b;
            background: #fee2e2;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-weight: 700;
            color: #1e3a5f;
        }

        select {
            width: 100%;
            padding: 15px 18px;
            border: none;
            outline: none;
            border-radius: 16px;
            background: #dbeafe;
            color: #243b53;
            font-size: 15px;
            box-shadow: inset 6px 6px 12px #b8c9dc, inset -6px -6px 12px #ffffff;
        }

        select:focus {
            box-shadow: inset 4px 4px 8px #b8c9dc, inset -4px -4px 8px #ffffff;
        }

        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            border: none;
            padding: 15px 20px;
            border-radius: 16px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            background: #dbeafe;
            color: #174a7e;
            box-shadow: 7px 7px 14px #b8c9dc, -7px -7px 14px #ffffff;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: #1769aa;
            color: white;
            box-shadow: 7px 7px 14px #9bb7d2, -7px -7px 14px #ffffff;
        }

        .info {
            margin-top: 25px;
            padding: 18px;
            border-radius: 18px;
            box-shadow: inset 5px 5px 10px #b8c9dc, inset -5px -5px 10px #ffffff;
            text-align: center;
            color: #607d98;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .card {
                padding: 25px;
            }
            .buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="card">

        <div class="icon">📖</div>

        <h1>Assign Teacher to Subject</h1>

        <p class="subtitle">
            Connect a teacher with a subject
        </p>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label for="teacher_id">
                    Select Teacher
                </label>
                <select name="teacher_id" id="teacher_id" required>
                    <option value="">-- Select Teacher --</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int)$teacher["id"] ?>">
                            <?= htmlspecialchars($teacher["full_name"]) ?> - <?= htmlspecialchars($teacher["employee_number"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subject_id">
                    Select Subject
                </label>
                <select name="subject_id" id="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= (int)$subject["id"] ?>">
                            <?= htmlspecialchars($subject["subject_name"]) ?> (<?= htmlspecialchars($subject["subject_code"]) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="buttons">
                <a href="index.php" class="btn">
                    📋 View Assignments
                </a>
                <a href="../../dashboard/admin.php" class="btn">
                    ← Dashboard
                </a>
                <button type="submit" class="btn btn-primary">
                    Assign Subject
                </button>
            </div>

        </form>

        <div class="info">
            💡 Select a teacher and a subject, then click
            <strong>Assign Subject</strong>.
        </div>

    </div>

</div>

</body>
</html>

