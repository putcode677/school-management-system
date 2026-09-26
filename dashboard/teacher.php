<?php

session_start();

require_once "../config/database.php";

// =========================
// CHECK TEACHER LOGIN
// =========================

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../auth/login.php");
    exit;
}


// =========================
// GET LOGGED-IN TEACHER
// =========================

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


// =========================
// TEACHER DATA
// =========================

$full_name = $teacher["full_name"];
$email = $teacher["email"];
$employee_number = $teacher["employee_number"];
$phone = $teacher["phone"] ?? "Not provided";
$address = $teacher["address"] ?? "Not provided";
$specialization = $teacher["specialization"] ?? "Not specified";
$hire_date = $teacher["hire_date"] ?? null;
$status = $teacher["status"] ?? "active";


// Format hire date
$formatted_hire_date = "Not provided";

if (!empty($hire_date)) {
    $formatted_hire_date = date("d M Y", strtotime($hire_date));
}


// Teacher initials
$name_parts = explode(" ", trim($full_name));

$initials = "";

foreach ($name_parts as $part) {

    if ($part !== "") {
        $initials .= strtoupper(substr($part, 0, 1));
    }

}

$initials = substr($initials, 0, 2);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Teacher Dashboard</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;
        }


        body {

            min-height: 100vh;

            background: #dbeafe;

            color: #243b53;

        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            width: 255px;

            height: 100vh;

            padding: 25px 18px;

            background: #dbeafe;

            box-shadow:
                8px 8px 18px #b8c9dc,
                -8px -8px 18px #ffffff;

            z-index: 1000;

        }


        .logo {

            text-align: center;

            margin-bottom: 35px;

        }


        .logo-circle {

            width: 68px;

            height: 68px;

            margin: auto;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            font-size: 30px;

            background: #dbeafe;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;

        }


        .logo h2 {

            margin-top: 15px;

            font-size: 20px;

            color: #1e3a5f;

        }


        .logo p {

            margin-top: 4px;

            color: #6b829c;

            font-size: 12px;

        }


        /* =========================
           MENU
        ========================= */

        .menu {

            list-style: none;

        }


        .menu li {

            margin-bottom: 9px;

        }


        .menu a {

            display: flex;

            align-items: center;

            gap: 14px;

            padding: 13px 16px;

            border-radius: 16px;

            color: #4d6680;

            text-decoration: none;

            transition: .25s;

        }


        .menu a:hover,
        .menu a.active {

            color: #1769aa;

            font-weight: 600;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;

        }


        .menu-icon {

            width: 28px;

            text-align: center;

            font-size: 18px;

        }


        /* =========================
           MAIN
        ========================= */

        .main {

            margin-left: 255px;

            padding: 30px;

        }


        /* =========================
           TOPBAR
        ========================= */

        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 30px;

        }


        .topbar h1 {

            font-size: 30px;

            color: #1e3a5f;

        }


        .topbar p {

            margin-top: 6px;

            color: #6b829c;

        }


        .teacher-profile {

            display: flex;

            align-items: center;

            gap: 13px;

            padding: 10px 18px;

            border-radius: 18px;

            background: #dbeafe;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;

        }


        .avatar {

            width: 46px;

            height: 46px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #dbeafe;

            color: #1769aa;

            font-size: 16px;

            font-weight: bold;

            box-shadow:
                inset 4px 4px 8px #b8c9dc,
                inset -4px -4px 8px #ffffff;

        }


        .profile-info strong {

            display: block;

            color: #1e3a5f;

            font-size: 14px;

        }


        .profile-info span {

            color: #6b829c;

            font-size: 12px;

        }


        /* =========================
           WELCOME
        ========================= */

        .welcome {

            position: relative;

            overflow: hidden;

            padding: 32px;

            margin-bottom: 30px;

            border-radius: 25px;

            background: #dbeafe;

            box-shadow:
                10px 10px 20px #b8c9dc,
                -10px -10px 20px #ffffff;

        }


        .welcome::after {

            content: "";

            position: absolute;

            width: 180px;

            height: 180px;

            right: -70px;

            top: -80px;

            border-radius: 50%;

            box-shadow:
                inset 8px 8px 15px #b8c9dc,
                inset -8px -8px 15px #ffffff;

            opacity: .7;

        }


        .welcome h2 {

            color: #1e3a5f;

            font-size: 25px;

            margin-bottom: 8px;

        }


        .welcome p {

            color: #6b829c;

        }


        /* =========================
           PROFILE INFORMATION
        ========================= */

        .profile-section {

            margin-bottom: 35px;

        }


        .section-title {

            margin-bottom: 20px;

        }


        .section-title h2 {

            color: #1e3a5f;

            font-size: 22px;

        }


        .section-title p {

            margin-top: 5px;

            color: #6b829c;

            font-size: 14px;

        }


        .profile-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

        }


        .profile-card {

            padding: 22px;

            border-radius: 20px;

            background: #dbeafe;

            box-shadow:
                8px 8px 18px #b8c9dc,
                -8px -8px 18px #ffffff;

        }


        .profile-card-icon {

            width: 48px;

            height: 48px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 15px;

            font-size: 22px;

            margin-bottom: 14px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;

        }


        .profile-card small {

            display: block;

            color: #6b829c;

            font-size: 12px;

            margin-bottom: 6px;

        }


        .profile-card strong {

            color: #1e3a5f;

            font-size: 15px;

            word-break: break-word;

        }


        .status {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 10px;

            color: #1769aa;

            background: #dbeafe;

            font-size: 12px;

            font-weight: bold;

            text-transform: capitalize;

            box-shadow:
                inset 3px 3px 6px #b8c9dc,
                inset -3px -3px 6px #ffffff;

        }


        /* =========================
           STATISTICS
        ========================= */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 22px;

            margin-bottom: 35px;

        }


        .stat-card {

            padding: 25px;

            text-align: center;

            border-radius: 22px;

            background: #dbeafe;

            box-shadow:
                8px 8px 18px #b8c9dc,
                -8px -8px 18px #ffffff;

        }


        .stat-icon {

            width: 55px;

            height: 55px;

            margin: 0 auto 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 17px;

            font-size: 25px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;

        }


        .stat-card h3 {

            font-size: 30px;

            color: #1769aa;

            margin-bottom: 5px;

        }


        .stat-card p {

            color: #6b829c;

            font-size: 14px;

        }


        /* =========================
           MANAGEMENT
        ========================= */

        .cards {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 22px;

        }


        .card {

            display: block;

            padding: 25px;

            border-radius: 22px;

            background: #dbeafe;

            color: #243b53;

            text-decoration: none;

            box-shadow:
                8px 8px 18px #b8c9dc,
                -8px -8px 18px #ffffff;

            transition: .3s;

        }


        .card:hover {

            transform: translateY(-5px);

        }


        .card:active {

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;

        }


        .card-icon {

            width: 55px;

            height: 55px;

            margin-bottom: 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 16px;

            font-size: 25px;

            box-shadow:
                inset 4px 4px 8px #b8c9dc,
                inset -4px -4px 8px #ffffff;

        }


        .card h3 {

            color: #1e3a5f;

            margin-bottom: 7px;

            font-size: 18px;

        }


        .card p {

            color: #6b829c;

            font-size: 13px;

            line-height: 1.5;

        }


        /* =========================
           LOGOUT
        ========================= */

        .logout {

            margin-top: 25px;

        }


        .logout a {

            display: flex;

            justify-content: center;

            align-items: center;

            gap: 10px;

            padding: 14px;

            border-radius: 16px;

            color: #4d6680;

            text-decoration: none;

            font-weight: 600;

            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;

        }


        .logout a:active {

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .stats,
            .cards {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .profile-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 800px) {

            .sidebar {

                width: 80px;

                padding: 20px 10px;

            }


            .logo h2,
            .logo p,
            .menu span,
            .logout span {

                display: none;

            }


            .main {

                margin-left: 80px;

                padding: 20px;

            }


            .menu a {

                justify-content: center;

                padding: 14px 5px;

            }

        }


        @media (max-width: 600px) {

            .stats,
            .cards,
            .profile-grid {

                grid-template-columns: 1fr;

            }


            .topbar {

                flex-direction: column;

                align-items: flex-start;

                gap: 20px;

            }


            .teacher-profile {

                width: 100%;

            }

        }

    </style>

</head>


<body>


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-circle">
                👨‍🏫
            </div>

            <h2>Teacher Portal</h2>

            <p>School Management System</p>

        </div>


        <ul class="menu">

            <li>

                <a href="teacher.php"
                   class="active">

                    <div class="menu-icon">
                        🏠
                    </div>

                    <span>
                        Dashboard
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        🏫
                    </div>

                    <span>
                        My Classes
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        📚
                    </div>

                    <span>
                        My Subjects
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        🎓
                    </div>

                    <span>
                        Students
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        📋
                    </div>

                    <span>
                        Attendance
                    </span>

                </a>

            </li>


            <li>

                <a href="#">

                    <div class="menu-icon">
                        📝
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

                <a href="#">

                    <div class="menu-icon">
                        👤
                    </div>

                    <span>
                        My Profile
                    </span>

                </a>

            </li>

        </ul>


        <div class="logout">

            <a href="../auth/logout.php">

                🚪

                <span>
                    Logout
                </span>

            </a>

        </div>

    </aside>


    <!-- =========================
         MAIN
    ========================== -->

    <main class="main">


        <!-- TOPBAR -->

        <div class="topbar">

            <div>

                <h1>
                    Teacher Dashboard
                </h1>

                <p>
                    Manage your teaching activities from one place.
                </p>

            </div>


            <div class="teacher-profile">

                <div class="avatar">

                    <?= htmlspecialchars($initials) ?>

                </div>


                <div class="profile-info">

                    <strong>
                        <?= htmlspecialchars($full_name) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars($email) ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- =========================
             WELCOME
        ========================== -->

        <section class="welcome">

            <h2>
                Welcome back,
                <?= htmlspecialchars($full_name) ?>
                👋
            </h2>

            <p>
                Here is your teaching profile and dashboard overview.
            </p>

        </section>


        <!-- =========================
             TEACHER PROFILE
        ========================== -->

        <section class="profile-section">

            <div class="section-title">

                <h2>
                    My Teacher Profile
                </h2>

                <p>
                    Your information from the school management system.
                </p>

            </div>


            <div class="profile-grid">


                <!-- Employee Number -->

                <div class="profile-card">

                    <div class="profile-card-icon">
                        🆔
                    </div>

                    <small>
                        Employee Number
                    </small>

                    <strong>
                        <?= htmlspecialchars($employee_number) ?>
                    </strong>

                </div>


                <!-- Specialization -->

                <div class="profile-card">

                    <div class="profile-card-icon">
                        🔬
                    </div>

                    <small>
                        Specialization
                    </small>

                    <strong>
                        <?= htmlspecialchars($specialization) ?>
                    </strong>

                </div>


                <!-- Phone -->

                <div class="profile-card">

                    <div class="profile-card-icon">
                        📱
                    </div>

                    <small>
                        Phone
                    </small>

                    <strong>
                        <?= htmlspecialchars($phone) ?>
                    </strong>

                </div>


                <!-- Email -->

                <div class="profile-card">

                    <div class="profile-card-icon">
                        📧
                    </div>

                    <small>
                        Email Address
                    </small>

                    <strong>
                        <?= htmlspecialchars($email) ?>
                    </strong>

                </div>


                <!-- Hire Date -->

                <div class="profile-card">

                    <div class="profile-card-icon">
                        📅
                    </div>

                    <small>
                        Hire Date
                    </small>

                    <strong>
                        <?= htmlspecialchars($formatted_hire_date) ?>
                    </strong>

                </div>


                <!-- Status -->

                <div class="profile-card">

                    <div class="profile-card-icon">
                        🟢
                    </div>

                    <small>
                        Account Status
                    </small>

                    <strong>

                        <span class="status">

                            <?= htmlspecialchars($status) ?>

                        </span>

                    </strong>

                </div>


            </div>

        </section>


        <!-- =========================
             STATISTICS
        ========================== -->

        <div class="section-title">

            <h2>
                Teaching Overview
            </h2>

            <p>
                Teaching statistics will appear here as modules are configured.
            </p>

        </div>


        <div class="stats">


            <div class="stat-card">

                <div class="stat-icon">
                    🏫
                </div>

                <h3>
                    0
                </h3>

                <p>
                    My Classes
                </p>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📚
                </div>

                <h3>
                    0
                </h3>

                <p>
                    My Subjects
                </p>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🎓
                </div>

                <h3>
                    0
                </h3>

                <p>
                    Students
                </p>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📝
                </div>

                <h3>
                    0
                </h3>

                <p>
                    Assignments
                </p>

            </div>

        </div>


        <!-- =========================
             TEACHING MANAGEMENT
        ========================== -->

        <div class="section-title">

            <h2>
                Teaching Management
            </h2>

            <p>
                Access your teaching tools and manage your classroom activities.
            </p>

        </div>


        <div class="cards">


            <a href="#" class="card">

                <div class="card-icon">
                    🏫
                </div>

                <h3>
                    My Classes
                </h3>

                <p>
                    View and manage classes assigned to you.
                </p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">
                    📚
                </div>

                <h3>
                    My Subjects
                </h3>

                <p>
                    View subjects you are responsible for teaching.
                </p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">
                    🎓
                </div>

                <h3>
                    Students
                </h3>

                <p>
                    View students and manage their academic information.
                </p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">
                    📋
                </div>

                <h3>
                    Attendance
                </h3>

                <p>
                    Record and monitor student attendance.
                </p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">
                    📝
                </div>

                <h3>
                    Assignments
                </h3>

                <p>
                    Create, manage and review assignments.
                </p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">
                    📊
                </div>

                <h3>
                    Results
                </h3>

                <p>
                    Enter and manage student examination results.
                </p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">
                    🗓️
                </div>

                <h3>
                    Timetable
                </h3>

                <p>
                    View your teaching timetable and schedules.
                </p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">
                    👤
                </div>

                <h3>
                    My Profile
                </h3>

                <p>
                    View and update your teacher profile.
                </p>

            </a>


        </div>


    </main>

</body>

</html>

