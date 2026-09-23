<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION["role"] !== "teacher") {
    header("Location: ../auth/login.php");
    exit;
}

$full_name = $_SESSION["full_name"];
$email = $_SESSION["email"];

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
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;

            background: #e0e5ec;

            color: #374151;

            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {

            position:fixed;

            left: 20px;
            top: 20px;
            bottom: 20px;

            width: 240px;

            background: #e0e5ec;

            border-radius: 25px;

            padding: 25px 18px;

            box-shadow:
                9px 9px 18px #bec3c9,
                -9px -9px 18px #ffffff;

        }


        .logo {

            text-align: center;

            font-size: 21px;

            font-weight: 700;

            margin-bottom: 35px;

            color: #2563eb;

        }


        .menu {

            display: flex;

            flex-direction: column;

            gap: 14px;

        }


        .menu a {

            text-decoration: none;

            color: #4b5563;

            padding: 14px 16px;

            border-radius: 15px;

            transition: 0.2s;

            font-size: 14px;

        }


        .menu a:hover {

            color: #2563eb;

            box-shadow:
                inset 4px 4px 8px #bec3c9,
                inset -4px -4px 8px #ffffff;

        }


        .menu .active {

            color: #2563eb;

            font-weight: 600;

            box-shadow:
                inset 4px 4px 8px #bec3c9,
                inset -4px -4px 8px #ffffff;

        }


        /* =========================
           MAIN
        ========================= */

        .main {

            margin-left: 280px;

            padding: 30px;

        }


        /* =========================
           TOP BAR
        ========================= */

        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 30px;

        }


        .page-title h1 {

            margin: 0;

            font-size: 28px;

        }


        .page-title p {

            margin-top: 6px;

            color: #6b7280;

        }


        .profile {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .avatar {

            width: 48px;

            height: 48px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

            color: #2563eb;

            box-shadow:
                6px 6px 12px #bec3c9,
                -6px -6px 12px #ffffff;

        }


        /* =========================
           WELCOME CARD
        ========================= */

        .welcome {

            padding: 28px;

            border-radius: 25px;

            margin-bottom: 30px;

            box-shadow:
                9px 9px 18px #bec3c9,
                -9px -9px 18px #ffffff;

        }


        .welcome h2 {

            margin: 0 0 10px;

            font-size: 24px;

        }


        .welcome p {

            margin: 5px 0;

            color: #6b7280;

        }


        /* =========================
           STAT CARDS
        ========================= */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

            margin-bottom: 30px;

        }


        .stat-card {

            padding: 22px;

            border-radius: 22px;

            text-align: center;

            box-shadow:
                8px 8px 16px #bec3c9,
                -8px -8px 16px #ffffff;

        }


        .stat-icon {

            font-size: 30px;

            margin-bottom: 8px;

        }


        .stat-card h3 {

            margin: 5px 0;

            font-size: 25px;

        }


        .stat-card p {

            margin: 0;

            color: #6b7280;

            font-size: 13px;

        }


        /* =========================
           DASHBOARD GRID
        ========================= */

        .cards {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 22px;

        }


        .card {

            padding: 25px;

            border-radius: 24px;

            text-align: center;

            transition: 0.25s;

            box-shadow:
                8px 8px 16px #bec3c9,
                -8px -8px 16px #ffffff;

        }


        .card:hover {

            transform: translateY(-4px);

        }


        .card-icon {

            width: 65px;

            height: 65px;

            margin: 0 auto 15px;

            border-radius: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;

            box-shadow:
                inset 5px 5px 10px #bec3c9,
                inset -5px -5px 10px #ffffff;

        }


        .card h3 {

            margin: 10px 0;

            font-size: 17px;

        }


        .card p {

            color: #6b7280;

            font-size: 13px;

            line-height: 1.5;

            min-height: 40px;

        }


        .card a {

            display: inline-block;

            margin-top: 12px;

            padding: 10px 18px;

            border-radius: 12px;

            text-decoration: none;

            color: #2563eb;

            font-size: 13px;

            font-weight: 600;

            box-shadow:
                5px 5px 10px #bec3c9,
                -5px -5px 10px #ffffff;

        }


        .card a:active {

            box-shadow:
                inset 4px 4px 8px #bec3c9,
                inset -4px -4px 8px #ffffff;

        }


        /* =========================
           LOGOUT
        ========================= */

        .logout {

            margin-top: 25px;

            text-align: center;

        }


        .logout a {

            display:flex;
            margin-top: 50px;
            padding: 13px;

            border-radius: 15px;

            text-decoration: none;

            color: #dc2626;

            font-weight: 600;

            box-shadow:
                5px 5px 10px #bec3c9,
                -5px -5px 10px #ffffff;

        }


        .logout a:active {

            box-shadow:
                inset 4px 4px 8px #bec3c9,
                inset -4px -4px 8px #ffffff;

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

        }


        @media (max-width: 750px) {

            .sidebar {

                position: relative;

                width: auto;

                left: 10px;

                right: 10px;

                top: 10px;

                bottom: auto;

            }


            .main {

                margin-left: 0;

                padding: 25px;

            }


            .menu {

                flex-direction: row;

                flex-wrap: wrap;

                justify-content: center;

            }

        }


        @media (max-width: 500px) {

            .stats,
            .cards {

                grid-template-columns: 1fr;

            }


            .topbar {

                align-items: flex-start;

            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">


    <div class="logo">

        🎓 SCHOOL SYSTEM

    </div>


    <nav class="menu">

        <a href="#" class="active">
            🏠 Dashboard
        </a>

        <a href="#">
            🏫 My Classes
        </a>

        <a href="#">
            📚 My Subjects
        </a>

        <a href="#">
            👨‍🎓 Students
        </a>

        <a href="#">
            📋 Attendance
        </a>

        <a href="#">
            📝 Assignments
        </a>

        <a href="#">
            📊 Results
        </a>

        <a href="#">
            📅 Timetable
        </a>

        <a href="#">
            👤 My Profile
        </a>


        <a href="../auth/logout.php">

            🚪 Logout

        </a>

    
    </nav>


   


</aside>


<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <!-- TOP BAR -->

    <div class="topbar">


        <div class="page-title">

            <h1>
                Teacher Dashboard
            </h1>

            <p>
                Manage your teaching activities
            </p>

        </div>


        <div class="profile">

            <div class="avatar">

                <?php
                echo strtoupper(
                    substr($full_name, 0, 1)
                );
                ?>

            </div>


            <div>

                <strong>
                    <?php
                    echo htmlspecialchars($full_name);
                    ?>
                </strong>

                <br>

                <small>
                    Teacher
                </small>

            </div>

        </div>


    </div>


    <!-- WELCOME -->

    <section class="welcome">

        <h2>

            Welcome back,
            <?php
            echo htmlspecialchars($full_name);
            ?>
            👋

        </h2>

        <p>

            Email:
            <?php
            echo htmlspecialchars($email);
            ?>

        </p>

        <p>

            Here's an overview of your teaching activities.

        </p>

    </section>


    <!-- STATISTICS -->

    <section class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                🏫
            </div>

            <h3>0</h3>

            <p>
                My Classes
            </p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                📚
            </div>

            <h3>0</h3>

            <p>
                My Subjects
            </p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                👨‍🎓
            </div>

            <h3>0</h3>

            <p>
                Students
            </p>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                📝
            </div>

            <h3>0</h3>

            <p>
                Assignments
            </p>

        </div>


    </section>


    <!-- DASHBOARD CARDS -->

    <section class="cards">


        <div class="card">

            <div class="card-icon">
                🏫
            </div>

            <h3>
                My Classes
            </h3>

            <p>
                View classes assigned to you.
            </p>

            <a href="#">
                View Classes
            </a>

        </div>


        <div class="card">

            <div class="card-icon">
                📚
            </div>

            <h3>
                My Subjects
            </h3>

            <p>
                View subjects you teach.
            </p>

            <a href="#">
                View Subjects
            </a>

        </div>


        <div class="card">

            <div class="card-icon">
                👨‍🎓
            </div>

            <h3>
                Students
            </h3>

            <p>
                View students in your classes.
            </p>

            <a href="#">
                View Students
            </a>

        </div>


        <div class="card">

            <div class="card-icon">
                📋
            </div>

            <h3>
                Attendance
            </h3>

            <p>
                Record student attendance.
            </p>

            <a href="#">
                Attendance
            </a>

        </div>


        <div class="card">

            <div class="card-icon">
                📝
            </div>

            <h3>
                Assignments
            </h3>

            <p>
                Create and manage assignments.
            </p>

            <a href="#">
                Assignments
            </a>

        </div>


        <div class="card">

            <div class="card-icon">
                📊
            </div>

            <h3>
                Results
            </h3>

            <p>
                Enter and manage student results.
            </p>

            <a href="#">
                Results
            </a>

        </div>


        <div class="card">

            <div class="card-icon">
                📅
            </div>

            <h3>
                Timetable
            </h3>

            <p>
                View your teaching timetable.
            </p>

            <a href="#">
                Timetable
            </a>

        </div>


        <div class="card">

            <div class="card-icon">
                👤
            </div>

            <h3>
                My Profile
            </h3>

            <p>
                View and update your profile.
            </p>

            <a href="#">
                Profile
            </a>

        </div>


    </section>


</main>


</body>

</html>

