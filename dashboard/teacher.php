<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

try {

    $stmt = $pdo->prepare(
        "SELECT
            teachers.id,
            teachers.employee_number,
            teachers.phone,
            teachers.address,
            teachers.specialization,
            teachers.hire_date,
            users.full_name,
            users.email,
            users.status
         FROM teachers
         INNER JOIN users
            ON teachers.user_id = users.id
         WHERE teachers.user_id = ?"
    );

    $stmt->execute([$user_id]);

    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        die("Teacher profile not found.");
    }

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}


$full_name = $teacher["full_name"];
$email = $teacher["email"];
$employee_number = $teacher["employee_number"];
$specialization = $teacher["specialization"] ?? "Not specified";


/* INITIALS */

$name_parts = explode(" ", trim($full_name));
$initials = "";

foreach ($name_parts as $part) {

    if ($part !== "") {
        $initials .= strtoupper(substr($part, 0, 1));
    }

}

$initials = substr($initials, 0, 2);


/* CURRENT PAGE */

$page = $_GET["page"] ?? "dashboard";


/* GET TEACHER CLASSES */

$classes = [];

try {

    $class_stmt = $pdo->prepare(
        "SELECT
            classes.id,
            classes.class_name,
            classes.class_code,
            classes.description
         FROM teacher_classes
         INNER JOIN classes
            ON teacher_classes.class_id = classes.id
         WHERE teacher_classes.teacher_id = ?
         ORDER BY classes.class_name ASC"
    );

    $class_stmt->execute([$teacher["id"]]);

    $classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}


/* GET TEACHER SUBJECTS */

$subjects = [];

try {

    $subject_stmt = $pdo->prepare(
        "SELECT
            subjects.id,
            subjects.subject_code,
            subjects.subject_name,
            subjects.description
         FROM teacher_subjects
         INNER JOIN subjects
            ON teacher_subjects.subject_id = subjects.id
         WHERE teacher_subjects.teacher_id = ?
         ORDER BY subjects.subject_name ASC"
    );

    $subject_stmt->execute([$teacher["id"]]);

    $subjects = $subject_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}


/* GET TEACHER STUDENTS */

$students = [];

try {

    $student_stmt = $pdo->prepare(
        "SELECT DISTINCT
            students.id,
            students.student_number,
            users.full_name,
            users.email,
            classes.class_name,
            classes.class_code
         FROM teacher_classes
         INNER JOIN student_classes
            ON student_classes.class_id = teacher_classes.class_id
         INNER JOIN students
            ON students.id = student_classes.student_id
         INNER JOIN users
            ON users.id = students.user_id
         INNER JOIN classes
            ON classes.id = teacher_classes.class_id
         WHERE teacher_classes.teacher_id = ?
         ORDER BY classes.class_name ASC, users.full_name ASC"
    );

    $student_stmt->execute([$teacher["id"]]);

    $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}

$attendance_statuses = [
    "present" => "Present",
    "absent" => "Absent",
    "late" => "Late",
    "excused" => "Excused",
];
$attendance_errors = [];
$attendance_message = $_SESSION["attendance_flash"] ?? "";
unset($_SESSION["attendance_flash"]);

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$attendance_class_id = filter_var(
    $_GET["class_id"] ?? 0,
    FILTER_VALIDATE_INT
);
$attendance_class_id = $attendance_class_id !== false && $attendance_class_id > 0
    ? $attendance_class_id
    : 0;
$attendance_date = $_GET["date"] ?? date("Y-m-d");
$today = date("Y-m-d");

function isValidAttendanceDate($value, string $today): bool
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }

    $parsedDate = DateTimeImmutable::createFromFormat("!Y-m-d", $value);

    return $parsedDate !== false
        && $parsedDate->format("Y-m-d") === $value
        && $value <= $today;
}

if (!isValidAttendanceDate($attendance_date, $today)) {
    $attendance_date = $today;
    if (($_GET["page"] ?? "") === "attendance") {
        $attendance_errors[] = "Choose a valid date that is not in the future.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $page === "attendance") {
    $posted_token = $_POST["csrf_token"] ?? "";
    $posted_class_id = filter_var(
        $_POST["class_id"] ?? 0,
        FILTER_VALIDATE_INT
    );
    $posted_date = $_POST["attendance_date"] ?? "";

    if (
        !is_string($posted_token)
        || !hash_equals($_SESSION["csrf_token"], $posted_token)
    ) {
        $attendance_errors[] = "Your session has expired. Reload the page and try again.";
    }

    if ($posted_class_id === false || $posted_class_id <= 0) {
        $attendance_errors[] = "Select a class assigned to you.";
    } else {
        $attendance_class_id = $posted_class_id;
    }

    if (!isValidAttendanceDate($posted_date, $today)) {
        $attendance_errors[] = "Choose a valid attendance date that is not in the future.";
    } else {
        $attendance_date = $posted_date;
    }

    $assigned_class_ids = array_map(
        static fn($class) => (int) $class["id"],
        $classes
    );
    if (!in_array($attendance_class_id, $assigned_class_ids, true)) {
        $attendance_errors[] = "You can only record attendance for a class assigned to you.";
    }

    if ($attendance_errors === []) {
        try {
            $roster_stmt = $pdo->prepare(
                "SELECT students.id
                 FROM student_classes
                 INNER JOIN students
                    ON students.id = student_classes.student_id
                 INNER JOIN users
                    ON users.id = students.user_id
                 WHERE student_classes.class_id = ?
                   AND users.status = 'active'
                 ORDER BY students.id"
            );
            $roster_stmt->execute([$attendance_class_id]);
            $roster_ids = array_map(
                "intval",
                $roster_stmt->fetchAll(PDO::FETCH_COLUMN)
            );
            $roster_lookup = array_fill_keys($roster_ids, true);
            $submitted_attendance = $_POST["attendance"] ?? null;

            if ($roster_ids === []) {
                $attendance_errors[] = "There are no active students in this class to mark.";
            } elseif (!is_array($submitted_attendance)) {
                $attendance_errors[] = "Attendance is missing for the selected class.";
            } else {
                foreach ($submitted_attendance as $student_id => $status) {
                    $student_id = filter_var($student_id, FILTER_VALIDATE_INT);
                    if (
                        $student_id === false
                        || !isset($roster_lookup[$student_id])
                        || !is_string($status)
                        || !isset($attendance_statuses[$status])
                    ) {
                        $attendance_errors[] = "The submitted attendance contains an invalid student or status.";
                        break;
                    }
                }

                foreach ($roster_ids as $student_id) {
                    if (!isset($submitted_attendance[$student_id])) {
                        $attendance_errors[] = "Choose an attendance status for every student.";
                        break;
                    }
                }
            }

            if ($attendance_errors === []) {
                $pdo->beginTransaction();
                $session_stmt = $pdo->prepare(
                    "INSERT INTO attendance_sessions
                        (class_id, attendance_date, created_by, updated_by)
                     VALUES (?, ?, ?, ?)
                     ON CONFLICT (class_id, attendance_date)
                     DO UPDATE SET
                        updated_by = EXCLUDED.updated_by,
                        updated_at = CURRENT_TIMESTAMP
                     RETURNING id"
                );
                $session_stmt->execute([
                    $attendance_class_id,
                    $attendance_date,
                    $teacher["id"],
                    $teacher["id"],
                ]);
                $attendance_session_id = (int) $session_stmt->fetchColumn();

                $existing_stmt = $pdo->prepare(
                    "SELECT id, status
                     FROM attendance_records
                     WHERE session_id = ? AND student_id = ?
                     FOR UPDATE"
                );
                $insert_stmt = $pdo->prepare(
                    "INSERT INTO attendance_records
                        (session_id, student_id, status, recorded_by)
                     VALUES (?, ?, ?, ?)"
                );
                $change_stmt = $pdo->prepare(
                    "INSERT INTO attendance_changes
                        (attendance_record_id, previous_status, new_status, changed_by)
                     VALUES (?, ?, ?, ?)"
                );
                $update_stmt = $pdo->prepare(
                    "UPDATE attendance_records
                     SET status = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?"
                );

                foreach ($roster_ids as $student_id) {
                    $status = $submitted_attendance[$student_id];
                    $existing_stmt->execute([
                        $attendance_session_id,
                        $student_id,
                    ]);
                    $existing_record = $existing_stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$existing_record) {
                        $insert_stmt->execute([
                            $attendance_session_id,
                            $student_id,
                            $status,
                            $teacher["id"],
                        ]);
                    } elseif ($existing_record["status"] !== $status) {
                        $change_stmt->execute([
                            $existing_record["id"],
                            $existing_record["status"],
                            $status,
                            $teacher["id"],
                        ]);
                        $update_stmt->execute([
                            $status,
                            $teacher["id"],
                            $existing_record["id"],
                        ]);
                    }
                }

                $pdo->commit();
                $_SESSION["attendance_flash"] = "Attendance saved successfully.";
                header(
                    "Location: teacher.php?page=attendance&class_id="
                    . $attendance_class_id
                    . "&date="
                    . rawurlencode($attendance_date)
                );
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Teacher attendance save failed: " . $e->getMessage());
            $attendance_errors[] = "Attendance could not be saved. Please try again.";
        }
    }
}

$attendance_class = null;
$attendance_roster = [];
$attendance_class_lookup = [];
foreach ($classes as $class) {
    $attendance_class_lookup[(int) $class["id"]] = $class;
}

if ($page === "attendance" && $attendance_class_id > 0) {
    $attendance_class = $attendance_class_lookup[$attendance_class_id] ?? null;
    if ($attendance_class !== null) {
        try {
            $roster_stmt = $pdo->prepare(
                "SELECT
                    students.id,
                    students.student_number,
                    users.full_name,
                    attendance_records.status
                 FROM student_classes
                 INNER JOIN students
                    ON students.id = student_classes.student_id
                 INNER JOIN users
                    ON users.id = students.user_id
                 LEFT JOIN attendance_sessions
                    ON attendance_sessions.class_id = student_classes.class_id
                   AND attendance_sessions.attendance_date = ?
                 LEFT JOIN attendance_records
                    ON attendance_records.session_id = attendance_sessions.id
                   AND attendance_records.student_id = students.id
                 WHERE student_classes.class_id = ?
                   AND users.status = 'active'
                 ORDER BY users.full_name ASC"
            );
            $roster_stmt->execute([$attendance_date, $attendance_class_id]);
            $attendance_roster = $roster_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Teacher attendance roster load failed: " . $e->getMessage());
            $attendance_errors[] = "The class attendance list could not be loaded.";
        }
    } elseif (!in_array($attendance_class_id, $assigned_class_ids ?? [], true)) {
        $attendance_errors[] = "You can only view attendance for a class assigned to you.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Teacher Dashboard</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #dbeafe;
            color: #1e3a8a;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }


        /* SIDEBAR */

        .sidebar {
            width: 250px;
            min-height: 100vh;
            padding: 25px 15px;
            background: #dbeafe;

            box-shadow:
                8px 0 16px #b8c9dc;

            position: fixed;
            left: 0;
            top: 0;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo h2 {
            color: #2563eb;
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin-bottom: 10px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 12px;

            text-decoration: none;
            color: #1e3a8a;

            border-radius: 15px;

            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        .menu-icon {

            width: 35px;
            height: 35px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            box-shadow:
                4px 4px 8px #b8c9dc,
                -4px -4px 8px #ffffff;
        }


        /* MAIN */

        .main {

            margin-left: 250px;

            width: calc(100% - 250px);

            padding: 35px;

            min-height: 100vh;
        }


        /* HEADER */

        .header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 30px;
        }

        .header h1 {

            color: #1e40af;

            margin-bottom: 5px;
        }

        .logout {

            padding: 12px 20px;

            text-decoration: none;

            color: #1e3a8a;

            border-radius: 15px;

            box-shadow:
                5px 5px 10px #b8c9dc,
                -5px -5px 10px #ffffff;
        }


        /* CONTENT */

        .content {

            padding: 30px;

            border-radius: 25px;

            box-shadow:
                8px 8px 16px #b8c9dc,
                -8px -8px 16px #ffffff;
        }


        /* PROFILE */

        .profile-card {

            display: flex;

            align-items: center;

            gap: 25px;

            padding: 25px;

            margin-bottom: 30px;

            border-radius: 20px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        .avatar {

            width: 80px;
            height: 80px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #dbeafe;

            font-size: 26px;

            font-weight: bold;

            color: #2563eb;

            box-shadow:
                6px 6px 12px #b8c9dc,
                -6px -6px 12px #ffffff;
        }

        .profile-info h2 {

            margin-bottom: 8px;
        }

        .profile-info p {

            margin: 5px 0;
        }


        /* STATS */

        .stats {

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 20px;
        }

        .stat-card {

            padding: 25px;

            text-align: center;

            border-radius: 20px;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        .stat-card h2 {

            color: #2563eb;

            margin-bottom: 8px;
        }


        /* PAGE TITLE */

        .page-title {

            margin-bottom: 25px;

            color: #1e40af;
        }


        /* INFO BOX */

        .info-box {

            padding: 25px;

            border-radius: 20px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        .info-box p {

            margin-bottom: 12px;

            line-height: 1.6;
        }


        /* MY CLASSES */

        .class-grid {

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 25px;
        }

        .class-card {

            padding: 25px;

            border-radius: 20px;

            text-align: center;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;

            transition: 0.2s;
        }

        .class-card:hover {

            transform: translateY(-3px);
        }

        .class-icon {

            width: 60px;
            height: 60px;

            margin: 0 auto 15px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 15px;

            font-size: 28px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        .class-card h3 {

            margin-bottom: 10px;

            color: #1e40af;
        }

        .class-code {

            font-weight: bold;

            color: #2563eb;

            margin-bottom: 12px;
        }

        .description {

            line-height: 1.5;
        }


        /* STUDENTS */

        .student-table-wrapper {

            overflow-x: auto;

            border-radius: 20px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;

            padding: 10px;
        }

        .student-table {

            width: 100%;

            border-collapse: separate;

            border-spacing: 0;

            min-width: 700px;
        }

        .student-table th {

            padding: 16px;

            text-align: left;

            color: #1e40af;

            font-size: 14px;
        }

        .student-table td {

            padding: 16px;

            border-top: 1px solid #c5d5e8;

            color: #1e3a8a;
        }

        .attendance-filter {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 14px;
            margin-bottom: 24px;
        }

        .attendance-filter label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .attendance-filter select,
        .attendance-filter input,
        .attendance-status {
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid #b8c9dc;
            border-radius: 10px;
            background: #fff;
            color: #1e3a8a;
            font: inherit;
        }

        .attendance-button {
            min-height: 42px;
            padding: 9px 16px;
            border: 0;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .attendance-error,
        .attendance-success {
            margin-bottom: 16px;
            padding: 12px 16px;
            border-radius: 10px;
        }

        .attendance-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .attendance-success {
            background: #dcfce7;
            color: #166534;
        }

        .attendance-note {
            margin-bottom: 16px;
            color: #526783;
        }

        .student-number {

            font-weight: bold;

            color: #2563eb;
        }

        .class-badge {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 12px;

            font-size: 13px;

            font-weight: bold;

            box-shadow:
                3px 3px 6px #b8c9dc,
                -3px -3px 6px #ffffff;
        }

        .empty-icon {

            font-size: 40px;

            margin-bottom: 10px;
        }


        /* RESPONSIVE */

        @media (max-width: 900px) {

            .stats {

                grid-template-columns: repeat(2, 1fr);
            }

            .class-grid {

                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 650px) {

            .container {

                display: block;
            }

            .sidebar {

                position: relative;

                width: 100%;

                min-height: auto;
            }

            .main {

                margin-left: 0;

                width: 100%;

                padding: 20px;
            }

            .stats {

                grid-template-columns: 1fr;
            }

            .class-grid {

                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>


<div class="container">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">

            <h2>Teacher Panel</h2>

        </div>


        <ul class="menu">


            <li>

                <a href="teacher.php?page=dashboard"
                   class="<?= $page === 'dashboard' ? 'active' : '' ?>">

                    <div class="menu-icon">🏠</div>

                    <span>Dashboard</span>

                </a>

            </li>


            <li>

                <a href="teacher.php?page=classes"
                   class="<?= $page === 'classes' ? 'active' : '' ?>">

                    <div class="menu-icon">📚</div>

                    <span>My Classes</span>

                </a>

            </li>


            <li>

                <a href="teacher.php?page=subjects"
                   class="<?= $page === 'subjects' ? 'active' : '' ?>">

                    <div class="menu-icon">📖</div>

                    <span>My Subjects</span>

                </a>

            </li>


            <li>

                <a href="teacher.php?page=students"
                   class="<?= $page === 'students' ? 'active' : '' ?>">

                    <div class="menu-icon">👨‍🎓</div>

                    <span>Students</span>

                </a>

            </li>


            <li>

                <a href="teacher.php?page=attendance"
                   class="<?= $page === 'attendance' ? 'active' : '' ?>">

                    <div class="menu-icon">📝</div>

                    <span>Attendance</span>

                </a>

            </li>


            <li>

                <a href="teacher.php?page=assignments"
                   class="<?= $page === 'assignments' ? 'active' : '' ?>">

                    <div class="menu-icon">📋</div>

                    <span>Assignments</span>

                </a>

            </li>


            <li>

                <a href="teacher.php?page=results"
                   class="<?= $page === 'results' ? 'active' : '' ?>">

                    <div class="menu-icon">📊</div>

                    <span>Results</span>

                </a>

            </li>


            <li>

                <a href="teacher.php?page=timetable"
                   class="<?= $page === 'timetable' ? 'active' : '' ?>">

                    <div class="menu-icon">🗓️</div>

                    <span>Timetable</span>

                </a>

            </li>


            <li>

                <a href="teacher.php?page=profile"
                   class="<?= $page === 'profile' ? 'active' : '' ?>">

                    <div class="menu-icon">👤</div>

                    <span>My Profile</span>

                </a>

            </li>


        </ul>

    </aside>


    <!-- RIGHT CONTENT -->

    <main class="main">


        <div class="header">

            <div>

                <h1>Teacher Dashboard</h1>

                <p>
                    Welcome back,
                    <?= htmlspecialchars($full_name) ?>
                </p>

            </div>


            <a class="logout" href="../auth/logout.php">

                Logout

            </a>

        </div>


        <div class="content">


            <!-- DASHBOARD -->

            <?php if ($page === "dashboard"): ?>


                <div class="profile-card">


                    <div class="avatar">

                        <?= htmlspecialchars($initials) ?>

                    </div>


                    <div class="profile-info">

                        <h2>
                            <?= htmlspecialchars($full_name) ?>
                        </h2>


                        <p>

                            <strong>Employee Number:</strong>

                            <?= htmlspecialchars($employee_number) ?>

                        </p>


                        <p>

                            <strong>Email:</strong>

                            <?= htmlspecialchars($email) ?>

                        </p>


                        <p>

                            <strong>Specialization:</strong>

                            <?= htmlspecialchars($specialization) ?>

                        </p>

                    </div>

                </div>


                <div class="stats">


                    <div class="stat-card">

                        <h2>
                            <?= count($classes) ?>
                        </h2>

                        <p>My Classes</p>

                    </div>


                    <div class="stat-card">

                        <h2>
                            <?= count($subjects) ?>
                        </h2>

                        <p>My Subjects</p>

                    </div>


                    <div class="stat-card">

                        <h2>
                            <?= count($students) ?>
                        </h2>

                        <p>Students</p>

                    </div>


                    <div class="stat-card">

                        <h2>0</h2>

                        <p>Assignments</p>

                    </div>


                </div>


            <!-- MY CLASSES -->

            <?php elseif ($page === "classes"): ?>


                <h2 class="page-title">

                    My Classes

                </h2>


                <?php if (empty($classes)): ?>


                    <div class="info-box">

                        <h3>No Classes Assigned</h3>

                        <p>

                            You currently have no classes assigned to you.

                        </p>

                    </div>


                <?php else: ?>


                    <div class="class-grid">


                        <?php foreach ($classes as $class): ?>


                            <div class="class-card">


                                <div class="class-icon">

                                    📚

                                </div>


                                <h3>

                                    <?= htmlspecialchars(
                                        $class["class_name"]
                                    ) ?>

                                </h3>


                                <p class="class-code">

                                    <?= htmlspecialchars(
                                        $class["class_code"]
                                    ) ?>

                                </p>


                                <p class="description">

                                    <?= !empty($class["description"])
                                        ? htmlspecialchars(
                                            $class["description"]
                                        )
                                        : "No description available." ?>

                                </p>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            <!-- PROFILE -->

            <?php elseif ($page === "profile"): ?>


                <h2 class="page-title">

                    My Profile

                </h2>


                <div class="info-box">


                    <p>

                        <strong>Full Name:</strong>

                        <?= htmlspecialchars(
                            $teacher["full_name"]
                        ) ?>

                    </p>


                    <p>

                        <strong>Employee Number:</strong>

                        <?= htmlspecialchars(
                            $teacher["employee_number"]
                        ) ?>

                    </p>


                    <p>

                        <strong>Email:</strong>

                        <?= htmlspecialchars(
                            $teacher["email"]
                        ) ?>

                    </p>


                    <p>

                        <strong>Phone:</strong>

                        <?= htmlspecialchars(
                            $teacher["phone"] ?? "Not provided"
                        ) ?>

                    </p>


                    <p>

                        <strong>Address:</strong>

                        <?= htmlspecialchars(
                            $teacher["address"] ?? "Not provided"
                        ) ?>

                    </p>


                    <p>

                        <strong>Specialization:</strong>

                        <?= htmlspecialchars(
                            $teacher["specialization"]
                            ?? "Not specified"
                        ) ?>

                    </p>


                    <p>

                        <strong>Hire Date:</strong>

                        <?= !empty($teacher["hire_date"])
                            ? date(
                                "d M Y",
                                strtotime($teacher["hire_date"])
                            )
                            : "Not provided" ?>

                    </p>


                    <p>

                        <strong>Status:</strong>

                        <?= htmlspecialchars(
                            $teacher["status"] ?? "active"
                        ) ?>

                    </p>


                </div>


            <!-- MY SUBJECTS -->

            <?php elseif ($page === "subjects"): ?>


                <h2 class="page-title">

                    My Subjects

                </h2>


                <?php if (empty($subjects)): ?>


                    <div class="info-box">

                        <h3>No Subjects Assigned</h3>

                        <p>

                            You currently have no subjects assigned to you.

                        </p>

                    </div>


                <?php else: ?>


                    <div class="class-grid">


                        <?php foreach ($subjects as $subject): ?>


                            <div class="class-card">


                                <div class="class-icon">

                                    📖

                                </div>


                                <h3>

                                    <?= htmlspecialchars(
                                        $subject["subject_name"]
                                    ) ?>

                                </h3>


                                <p class="class-code">

                                    <?= htmlspecialchars(
                                        $subject["subject_code"]
                                    ) ?>

                                </p>


                                <p class="description">

                                    <?= !empty($subject["description"])
                                        ? htmlspecialchars(
                                            $subject["description"]
                                        )
                                        : "No description available." ?>

                                </p>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            <!-- STUDENTS -->

            <?php elseif ($page === "students"): ?>


                <h2 class="page-title">

                    My Students

                </h2>


                <?php if (empty($students)): ?>


                    <div class="info-box">

                        <div class="empty-icon">👨‍🎓</div>

                        <h3>No Students Found</h3>

                        <p>

                            There are currently no students assigned
                            to your classes.

                        </p>

                    </div>


                <?php else: ?>


                    <div class="student-table-wrapper">

                        <table class="student-table">

                            <thead>

                                <tr>

                                    <th>#</th>

                                    <th>Student Number</th>

                                    <th>Full Name</th>

                                    <th>Email</th>

                                    <th>Class</th>

                                    <th>Code</th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php $number = 1; ?>


                                <?php foreach ($students as $student): ?>


                                    <tr>

                                        <td>

                                            <?= $number++ ?>

                                        </td>


                                        <td class="student-number">

                                            <?= htmlspecialchars(
                                                $student["student_number"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $student["full_name"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $student["email"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <span class="class-badge">

                                                <?= htmlspecialchars(
                                                    $student["class_name"]
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $student["class_code"]
                                            ) ?>

                                        </td>

                                    </tr>


                                <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php endif; ?>


            <!-- ATTENDANCE -->

            <?php elseif ($page === "attendance"): ?>

                <h2 class="page-title">Class Attendance</h2>

                <?php if ($attendance_message !== ""): ?>
                    <div class="attendance-success" role="status">
                        <?= htmlspecialchars($attendance_message, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($attendance_errors as $attendance_error): ?>
                    <div class="attendance-error" role="alert">
                        <?= htmlspecialchars($attendance_error, ENT_QUOTES, "UTF-8") ?>
                    </div>
                <?php endforeach; ?>

                <form method="get" class="attendance-filter">
                    <input type="hidden" name="page" value="attendance">
                    <div>
                        <label for="attendance-class">Class</label>
                        <select id="attendance-class" name="class_id" required>
                            <option value="">Select a class</option>
                            <?php foreach ($classes as $class): ?>
                                <option
                                    value="<?= (int) $class["id"] ?>"
                                    <?= (int) $class["id"] === $attendance_class_id ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($class["class_name"], ENT_QUOTES, "UTF-8") ?>
                                    (<?= htmlspecialchars($class["class_code"], ENT_QUOTES, "UTF-8") ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="attendance-date">Date</label>
                        <input
                            type="date"
                            id="attendance-date"
                            name="date"
                            value="<?= htmlspecialchars($attendance_date, ENT_QUOTES, "UTF-8") ?>"
                            max="<?= htmlspecialchars($today, ENT_QUOTES, "UTF-8") ?>"
                            required
                        >
                    </div>
                    <button class="attendance-button" type="submit">Load class</button>
                </form>

                <p class="attendance-note">
                    Record attendance for today or a past date. Saving a correction keeps an audit history.
                </p>

                <?php if ($attendance_class !== null && $attendance_roster !== []): ?>
                    <h3>
                        <?= htmlspecialchars($attendance_class["class_name"], ENT_QUOTES, "UTF-8") ?>
                        — <?= htmlspecialchars(date("F j, Y", strtotime($attendance_date)), ENT_QUOTES, "UTF-8") ?>
                    </h3>

                    <div class="student-table-wrapper">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
                            <input type="hidden" name="class_id" value="<?= $attendance_class_id ?>">
                            <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($attendance_date, ENT_QUOTES, "UTF-8") ?>">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th>Student number</th>
                                        <th>Student</th>
                                        <th>Attendance status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_roster as $student): ?>
                                        <?php
                                                        $submitted_values = $_POST["attendance"] ?? [];
                                                        $submitted_status = is_array($submitted_values)
                                                            ? ($submitted_values[$student["id"]] ?? "")
                                                            : "";
                                                        $current_status = is_string($submitted_status)
                                                            && isset($attendance_statuses[$submitted_status])
                                                            ? $submitted_status
                                                            : ($student["status"] ?? "");
                                                        ?>
                                        <tr>
                                            <td class="student-number">
                                                <?= htmlspecialchars($student["student_number"], ENT_QUOTES, "UTF-8") ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($student["full_name"], ENT_QUOTES, "UTF-8") ?>
                                            </td>
                                            <td>
                                                <select
                                                    class="attendance-status"
                                                    name="attendance[<?= (int) $student["id"] ?>]"
                                                    aria-label="Attendance for <?= htmlspecialchars($student["full_name"], ENT_QUOTES, "UTF-8") ?>"
                                                    required
                                                >
                                                    <option value="">Select status</option>
                                                    <?php foreach ($attendance_statuses as $status_value => $status_label): ?>
                                                        <option
                                                            value="<?= htmlspecialchars($status_value, ENT_QUOTES, "UTF-8") ?>"
                                                            <?= $current_status === $status_value ? "selected" : "" ?>
                                                        >
                                                            <?= htmlspecialchars($status_label, ENT_QUOTES, "UTF-8") ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button class="attendance-button" type="submit">
                                Save attendance
                            </button>
                        </form>
                    </div>
                <?php elseif ($attendance_class !== null): ?>
                    <div class="info-box">
                        <p>No active students are currently assigned to this class.</p>
                    </div>
                <?php elseif ($classes === []): ?>
                    <div class="info-box">
                        <p>No classes are assigned to your teacher account.</p>
                    </div>
                <?php endif; ?>


            <!-- ASSIGNMENTS -->

            <?php elseif ($page === "assignments"): ?>


                <h2 class="page-title">

                    Assignments

                </h2>


                <div class="info-box">

                    <p>

                        Assignment management is not available yet.

                    </p>

                </div>


            <!-- RESULTS -->

            <?php elseif ($page === "results"): ?>


                <h2 class="page-title">

                    Results

                </h2>


                <div class="info-box">

                    <p>

                        Results management is not available yet.

                    </p>

                </div>


            <!-- TIMETABLE -->

            <?php elseif ($page === "timetable"): ?>


                <h2 class="page-title">

                    Timetable

                </h2>


                <div class="info-box">

                    <p>

                        Timetable viewing is not available yet.

                    </p>

                </div>


            <?php endif; ?>


        </div>

    </main>

</div>

</body>

</html>