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

                        <h2>0</h2>

                        <p>My Subjects</p>

                    </div>


                    <div class="stat-card">

                        <h2>0</h2>

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


            <!-- OTHER PAGES -->

            <?php elseif ($page === "subjects"): ?>

                <h2 class="page-title">My Subjects</h2>

                <div class="info-box">

                    <p>
                        My Subjects content will appear here.
                    </p>

                </div>


            <?php elseif ($page === "students"): ?>

                <h2 class="page-title">Students</h2>

                <div class="info-box">

                    <p>
                        Students content will appear here.
                    </p>

                </div>


            <?php elseif ($page === "attendance"): ?>

                <h2 class="page-title">Attendance</h2>

                <div class="info-box">

                    <p>
                        Attendance content will appear here.
                    </p>

                </div>


            <?php elseif ($page === "assignments"): ?>

                <h2 class="page-title">Assignments</h2>

                <div class="info-box">

                    <p>
                        Assignments content will appear here.
                    </p>

                </div>


            <?php elseif ($page === "results"): ?>

                <h2 class="page-title">Results</h2>

                <div class="info-box">

                    <p>
                        Results content will appear here.
                    </p>

                </div>


            <?php elseif ($page === "timetable"): ?>

                <h2 class="page-title">Timetable</h2>

                <div class="info-box">

                    <p>
                        Timetable content will appear here.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </main>

</div>

</body>

</html>