<?php

session_start();

// Protect dashboard
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Allow only students
if ($_SESSION["role"] !== "student") {
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

    <title>Student Dashboard</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
        }

        /* Sidebar */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;

            width: 240px;
            height: 100vh;

            background: #1e293b;
            color: white;

            padding: 25px 15px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;

            color: white;
            text-decoration: none;

            padding: 13px 15px;
            margin-bottom: 8px;

            border-radius: 6px;
        }

        .sidebar a:hover {
            background: #334155;
        }

        .logout {
            margin-top: 30px;
            background: #dc2626;
        }

        .logout:hover {
            background: #b91c1c !important;
        }

        /* Main content */

        .main {
            margin-left: 240px;
            padding: 30px;
        }

        .topbar {
            background: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 25px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .topbar h1 {
            margin-bottom: 8px;
        }

        .topbar p {
            color: #64748b;
        }

        /* Cards */

        .cards {
            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(200px, 1fr));

            gap: 20px;
        }

        .card {
            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.08);

            text-align: center;
        }

        .card h3 {
            margin-bottom: 10px;
        }

        .card p {
            color: #64748b;
        }

        .icon {
            font-size: 35px;
            margin-bottom: 12px;
        }

        /* Profile */

        .profile {
            background: white;

            margin-top: 25px;

            padding: 25px;

            border-radius: 10px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .profile h2 {
            margin-bottom: 15px;
        }

        .profile p {
            margin: 10px 0;
        }

        /* Mobile */

        @media (max-width: 768px) {

            .sidebar {
                width: 100%;
                height: auto;

                position: relative;
            }

            .main {
                margin-left: 0;
            }

            .sidebar a {
                display: inline-block;
            }

        }

    </style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <h2>🎓 Student</h2>

    <a href="student.php">
        🏠 Dashboard
    </a>

    <a href="#">
        📚 My Subjects
    </a>

    <a href="#">
        📝 My Results
    </a>

    <a href="#">
        📅 Timetable
    </a>

    <a href="#">
        📢 Announcements
    </a>

    <a href="#">
        💰 Fees
    </a>

    <a href="#">
        👤 My Profile
    </a>

    <a class="logout" href="../auth/logout.php">
        🚪 Logout
    </a>

</div>


<!-- MAIN CONTENT -->

<div class="main">

    <!-- TOP BAR -->

    <div class="topbar">

        <h1>
            Welcome, <?php echo htmlspecialchars($full_name); ?> 👋
        </h1>

        <p>
            Welcome to your Student Dashboard.
        </p>

    </div>


    <!-- DASHBOARD CARDS -->

    <div class="cards">

        <div class="card">

            <div class="icon">📚</div>

            <h3>My Subjects</h3>

            <p>
                View your registered subjects.
            </p>

        </div>


        <div class="card">

            <div class="icon">📝</div>

            <h3>My Results</h3>

            <p>
                View your examination results.
            </p>

        </div>


        <div class="card">

            <div class="icon">📅</div>

            <h3>Timetable</h3>

            <p>
                View your class timetable.
            </p>

        </div>


        <div class="card">

            <div class="icon">💰</div>

            <h3>Fees</h3>

            <p>
                Check your school fees.
            </p>

        </div>

    </div>


    <!-- PROFILE -->

    <div class="profile">

        <h2>My Account</h2>

        <p>
            <strong>Name:</strong>
            <?php echo htmlspecialchars($full_name); ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?php echo htmlspecialchars($email); ?>
        </p>

        <p>
            <strong>Role:</strong>
            Student
        </p>

        <p>
            <strong>Status:</strong>
            Active
        </p>

    </div>

</div>

</body>

</html>