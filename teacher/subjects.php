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

    /*
     * GET LOGGED-IN TEACHER
     */

    $stmt = $pdo->prepare(
        "SELECT
            teachers.id,
            teachers.employee_number,
            users.full_name
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


    /*
     * GET TEACHER SUBJECTS
     */

    $stmt = $pdo->prepare(
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

    $stmt->execute([$teacher["id"]]);

    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>My Subjects</title>

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


/* =========================
   SIDEBAR
========================= */

.sidebar {

    width: 250px;

    padding: 25px 15px;

    background: #dbeafe;

    box-shadow:
        8px 8px 16px #b8c9dc,
        -8px -8px 16px #ffffff;

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

    margin-bottom: 15px;

}

.menu a {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 14px;

    text-decoration: none;

    color: #1e3a8a;

    border-radius: 15px;

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


/* =========================
   MAIN
========================= */

.main {

    flex: 1;

    padding: 35px;

}

.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;

}

.header h1 {

    color: #1e40af;

}

.header p {

    margin-top: 7px;

    color: #64748b;

}

.back {

    padding: 12px 20px;

    text-decoration: none;

    color: #1e3a8a;

    border-radius: 15px;

    box-shadow:
        5px 5px 10px #b8c9dc,
        -5px -5px 10px #ffffff;

}


/* =========================
   INFO
========================= */

.info {

    padding: 25px;

    margin-bottom: 30px;

    border-radius: 22px;

    box-shadow:
        8px 8px 16px #b8c9dc,
        -8px -8px 16px #ffffff;

}

.info h2 {

    margin-bottom: 8px;

    color: #1e40af;

}

.info p {

    color: #64748b;

}


/* =========================
   SUBJECTS
========================= */

.subject-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 22px;

}

.subject-card {

    padding: 25px;

    border-radius: 22px;

    box-shadow:
        8px 8px 16px #b8c9dc,
        -8px -8px 16px #ffffff;

    transition: .25s;

}

.subject-card:hover {

    transform: translateY(-4px);

}

.subject-icon {

    width: 55px;

    height: 55px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 18px;

    border-radius: 16px;

    font-size: 25px;

    box-shadow:
        inset 5px 5px 10px #b8c9dc,
        inset -5px -5px 10px #ffffff;

}

.subject-code {

    display: inline-block;

    margin-bottom: 10px;

    padding: 6px 12px;

    border-radius: 10px;

    color: #2563eb;

    font-size: 12px;

    font-weight: bold;

    box-shadow:
        inset 3px 3px 6px #b8c9dc,
        inset -3px -3px 6px #ffffff;

}

.subject-card h3 {

    color: #1e3a8a;

    margin-bottom: 10px;

}

.subject-card p {

    color: #64748b;

    line-height: 1.5;

    font-size: 14px;

}


/* =========================
   EMPTY
========================= */

.empty {

    padding: 50px;

    text-align: center;

    border-radius: 22px;

    box-shadow:
        8px 8px 16px #b8c9dc,
        -8px -8px 16px #ffffff;

}

.empty-icon {

    font-size: 50px;

    margin-bottom: 15px;

}

.empty h2 {

    color: #1e40af;

    margin-bottom: 8px;

}

.empty p {

    color: #64748b;

}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1000px) {

    .subject-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}

@media (max-width: 700px) {

    .container {

        flex-direction: column;

    }

    .sidebar {

        width: 100%;

    }

    .main {

        padding: 20px;

    }

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 20px;

    }

    .subject-grid {

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

                <a href="../dashboard/teacher.php">

                    <div class="menu-icon">
                        🏠
                    </div>

                    <span>
                        Dashboard
                    </span>

                </a>

            </li>


            <li>

                <a href="../teacher/classes.php">

                    <div class="menu-icon">
                        📚
                    </div>

                    <span>
                        My Classes
                    </span>

                </a>

            </li>


            <li>

                <a href="subjects.php"
                   class="active">

                    <div class="menu-icon">
                        📖
                    </div>

                    <span>
                        My Subjects
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        👨‍🎓
                    </div>

                    <span>
                        Students
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        📝
                    </div>

                    <span>
                        Attendance
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        📋
                    </div>

                    <span>
                        Assignments
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        📊
                    </div>

                    <span>
                        Results
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        🗓️
                    </div>

                    <span>
                        Timetable
                    </span>

                </a>

            </li>


            <li>

                <a href="../teacher/profile.php">

                    <div class="menu-icon">
                        👤
                    </div>

                    <span>
                        My Profile
                    </span>

                </a>

            </li>

        </ul>

    </aside>


    <!-- MAIN -->

    <main class="main">


        <div class="header">

            <div>

                <h1>
                    My Subjects
                </h1>

                <p>
                    Subjects assigned to <?= htmlspecialchars($teacher["full_name"]) ?>
                </p>

            </div>


            <a href="../dashboard/teacher.php"
               class="back">

                ← Dashboard

            </a>

        </div>


        <div class="info">

            <h2>
                Teaching Subjects
            </h2>

            <p>
                Here you can view all subjects assigned to you.
            </p>

        </div>


        <?php if (empty($subjects)): ?>

            <div class="empty">

                <div class="empty-icon">
                    📚
                </div>

                <h2>
                    No Subjects Assigned
                </h2>

                <p>
                    You currently have no subjects assigned to you.
                </p>

            </div>


        <?php else: ?>

            <div class="subject-grid">

                <?php foreach ($subjects as $subject): ?>

                    <div class="subject-card">

                        <div class="subject-icon">
                            📖
                        </div>

                        <span class="subject-code">
                            <?= htmlspecialchars($subject["subject_code"]) ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($subject["subject_name"]) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars(
                                $subject["description"] ?: "No description available."
                            ) ?>
                        </p>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


    </main>

</div>

</body>

</html>
