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
$phone = $teacher["phone"] ?? "Not provided";
$address = $teacher["address"] ?? "Not provided";
$specialization = $teacher["specialization"] ?? "Not specified";
$hire_date = $teacher["hire_date"] ?? null;
$status = $teacher["status"] ?? "active";

$formatted_hire_date = "Not provided";

if (!empty($hire_date)) {
    $formatted_hire_date = date("d M Y", strtotime($hire_date));
}

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
            padding: 25px 15px;
            background: #dbeafe;
            box-shadow: 8px 8px 16px #b8c9dc,
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

        .menu a:hover {
            box-shadow: inset 5px 5px 10px #b8c9dc,
                        inset -5px -5px 10px #ffffff;
        }

        .menu-icon {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            box-shadow: 4px 4px 8px #b8c9dc,
                        -4px -4px 8px #ffffff;
        }

        /* MAIN */

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

        .logout {
            padding: 12px 20px;
            text-decoration: none;
            color: #1e3a8a;
            border-radius: 15px;
            box-shadow: 5px 5px 10px #b8c9dc,
                        -5px -5px 10px #ffffff;
        }

        /* PROFILE */

        .profile-card {
            display: flex;
            align-items: center;
            gap: 25px;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 25px;
            box-shadow: 8px 8px 16px #b8c9dc,
                        -8px -8px 16px #ffffff;
        }

        .avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            font-size: 30px;
            font-weight: bold;
            color: #2563eb;
            box-shadow: inset 6px 6px 12px #b8c9dc,
                        inset -6px -6px 12px #ffffff;
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
            margin-bottom: 30px;
        }

        .stat-card {
            padding: 25px;
            text-align: center;
            border-radius: 20px;
            box-shadow: 7px 7px 14px #b8c9dc,
                        -7px -7px 14px #ffffff;
        }

        .stat-card h2 {
            color: #2563eb;
            margin-bottom: 8px;
        }

        /* CARDS */

        .section-title {
            margin-bottom: 20px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .card {
            padding: 25px;
            text-align: center;
            border-radius: 20px;
            text-decoration: none;
            color: #1e3a8a;
            box-shadow: 7px 7px 14px #b8c9dc,
                        -7px -7px 14px #ffffff;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card-icon {
            font-size: 35px;
            margin-bottom: 12px;
        }

        /* RESPONSIVE */

        @media (max-width: 900px) {

            .stats,
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 650px) {

            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .stats,
            .cards {
                grid-template-columns: 1fr;
            }

            .main {
                padding: 20px;
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
                <a href="teacher.php">
                    <div class="menu-icon">🏠</div>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">📚</div>
                    <span>My Classes</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">📖</div>
                    <span>My Subjects</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">👨‍🎓</div>
                    <span>Students</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">📝</div>
                    <span>Attendance</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">📋</div>
                    <span>Assignments</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">📊</div>
                    <span>Results</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">🗓️</div>
                    <span>Timetable</span>
                </a>
            </li>

            <!-- FIXED MY PROFILE -->

            <li>
                <a href="../teacher/profile.php">
                    <div class="menu-icon">👤</div>
                    <span>My Profile</span>
                </a>
            </li>

        </ul>

    </aside>


    <!-- MAIN -->

    <main class="main">

        <div class="header">

            <div>
                <h1>Teacher Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($full_name) ?></p>
            </div>

            <a class="logout" href="../auth/logout.php">
                Logout
            </a>

        </div>


        <!-- PROFILE -->

        <div class="profile-card">

            <div class="avatar">
                <?= htmlspecialchars($initials) ?>
            </div>

            <div class="profile-info">

                <h2><?= htmlspecialchars($full_name) ?></h2>

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


        <!-- STATS -->

        <div class="stats">

            <div class="stat-card">
                <h2>0</h2>
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


        <!-- MANAGEMENT -->

        <h2 class="section-title">
            Teacher Management
        </h2>

        <div class="cards">

            <a href="#" class="card">

                <div class="card-icon">📚</div>

                <h3>My Classes</h3>

                <p>View assigned classes</p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">📖</div>

                <h3>My Subjects</h3>

                <p>View teaching subjects</p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">👨‍🎓</div>

                <h3>Students</h3>

                <p>View my students</p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">📝</div>

                <h3>Attendance</h3>

                <p>Manage attendance</p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">📋</div>

                <h3>Assignments</h3>

                <p>Manage assignments</p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">📊</div>

                <h3>Results</h3>

                <p>Manage student results</p>

            </a>


            <a href="#" class="card">

                <div class="card-icon">🗓️</div>

                <h3>Timetable</h3>

                <p>View timetable</p>

            </a>


            <a href="../teacher/profile.php" class="card">

                <div class="card-icon">👤</div>

                <h3>My Profile</h3>

                <p>View my profile</p>

            </a>

        </div>

    </main>

</div>

</body>

</html>