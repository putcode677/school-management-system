<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "Administrator";
$email = $_SESSION["email"] ?? "";

require_once "../config/database.php";

// Count students
$stmt = $pdo->query("SELECT COUNT(*) FROM students");
$total_students = (int) $stmt->fetchColumn();

// Count teachers
$stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
$total_teachers = (int) $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            background: #e0e5ec;
            color: #333;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            padding: 25px 18px;
            background: #e0e5ec;
            box-shadow: 8px 8px 18px #bec3c9,
                        -8px -8px 18px #ffffff;
            z-index: 1000;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 65px;
            height: 65px;
            margin: auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            background: #e0e5ec;
            box-shadow: 8px 8px 16px #bec3c9,
                        -8px -8px 16px #ffffff;
        }

        .logo h2 {
            margin-top: 15px;
            font-size: 20px;
        }

        .logo p {
            margin-top: 4px;
            font-size: 12px;
            color: #777;
        }

        /* Menu */
        .menu {
            list-style: none;
        }

        .menu li {
            margin-bottom: 10px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 16px;
            color: #555;
            text-decoration: none;
            border-radius: 15px;
            transition: 0.3s;
        }

        .menu a:hover,
        .menu a.active {
            color: #222;
            font-weight: 600;
            box-shadow: inset 5px 5px 10px #bec3c9,
                        inset -5px -5px 10px #ffffff;
        }

        .menu-icon {
            width: 30px;
            text-align: center;
            font-size: 18px;
        }

        /* Main */
        .main {
            margin-left: 250px;
            padding: 30px;
        }

        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .topbar h1 {
            font-size: 28px;
        }

        .topbar p {
            margin-top: 5px;
            color: #777;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 18px;
            border-radius: 18px;
            box-shadow: 7px 7px 14px #bec3c9,
                        -7px -7px 14px #ffffff;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e0e5ec;
            font-size: 18px;
            font-weight: bold;
            box-shadow: inset 4px 4px 8px #bec3c9,
                        inset -4px -4px 8px #ffffff;
        }

        .profile-info strong {
            display: block;
            font-size: 14px;
        }

        .profile-info span {
            font-size: 12px;
            color: #777;
        }

        /* Welcome */
        .welcome {
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 25px;
            box-shadow: 10px 10px 20px #bec3c9,
                        -10px -10px 20px #ffffff;
        }

        .welcome h2 {
            margin-bottom: 8px;
            font-size: 25px;
        }

        .welcome p {
            color: #777;
        }

        /* Statistics */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
            margin-bottom: 35px;
        }

        .stat-card {
            padding: 25px;
            text-align: center;
            border-radius: 22px;
            box-shadow: 8px 8px 18px #bec3c9,
                        -8px -8px 18px #ffffff;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-icon {
            margin-bottom: 12px;
            font-size: 28px;
        }

        .stat-card h3 {
            margin-bottom: 5px;
            font-size: 30px;
        }

        .stat-card p {
            color: #777;
            font-size: 14px;
        }

        /* Section */
        .section-title {
            margin-bottom: 20px;
        }

        .section-title h2 {
            font-size: 22px;
        }

        .section-title p {
            margin-top: 5px;
            color: #777;
            font-size: 14px;
        }

        /* Management Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .card {
            display: block;
            padding: 25px;
            color: #333;
            text-decoration: none;
            border-radius: 22px;
            box-shadow: 8px 8px 18px #bec3c9,
                        -8px -8px 18px #ffffff;
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card:active {
            box-shadow: inset 5px 5px 10px #bec3c9,
                        inset -5px -5px 10px #ffffff;
        }

        .card-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            border-radius: 16px;
            font-size: 25px;
            box-shadow: inset 4px 4px 8px #bec3c9,
                        inset -4px -4px 8px #ffffff;
        }

        .card h3 {
            margin-bottom: 7px;
            font-size: 18px;
        }

        .card p {
            color: #777;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Logout */
        .logout {
            margin-top: 25px;
        }

        .logout a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px;
            color: #555;
            text-decoration: none;
            font-weight: 600;
            border-radius: 16px;
            box-shadow: 7px 7px 14px #bec3c9,
                        -7px -7px 14px #ffffff;
        }

        .logout a:active {
            box-shadow: inset 5px 5px 10px #bec3c9,
                        inset -5px -5px 10px #ffffff;
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .stats,
            .cards {
                grid-template-columns: repeat(2, 1fr);
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

            .topbar {
                gap: 15px;
            }
        }

        @media (max-width: 600px) {
            .stats,
            .cards {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-profile {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="logo">
            <div class="logo-icon">🏫</div>
            <h2>School Admin</h2>
            <p>Management System</p>
        </div>

        <ul class="menu">

            <li>
                <a href="admin.php" class="active">
                    <div class="menu-icon">🏠</div>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="../admin/students/index.php">
                    <div class="menu-icon">🎓</div>
                    <span>Students</span>
                </a>
            </li>

            <li>
                <a href="../admin/teachers/index.php">
                    <div class="menu-icon">👨‍🏫</div>
                    <span>Teachers</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">👨‍👩‍👧</div>
                    <span>Parents</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">📚</div>
                    <span>Subjects</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">🏫</div>
                    <span>Classes</span>
                </a>
            </li>

            <li>
                <a href="#">
                    <div class="menu-icon">📋</div>
                    <span>Attendance</span>
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
                    <div class="menu-icon">⚙️</div>
                    <span>Settings</span>
                </a>
            </li>

        </ul>

        <div class="logout">
            <a href="../auth/logout.php">
                <div>🚪</div>
                <span>Logout</span>
            </a>
        </div>

    </aside>


    <!-- Main Content -->
    <main class="main">

        <!-- Topbar -->
        <div class="topbar">

            <div>
                <h1>Admin Dashboard</h1>
                <p>Manage your school system from one place.</p>
            </div>

            <div class="admin-profile">

                <div class="avatar">
                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                </div>

                <div class="profile-info">
                    <strong><?php echo htmlspecialchars($full_name); ?></strong>
                    <span><?php echo htmlspecialchars($email); ?></span>
                </div>

            </div>

        </div>


        <!-- Welcome -->
        <section class="welcome">

            <h2>
                Welcome back, <?php echo htmlspecialchars($full_name); ?> 👋
            </h2>

            <p>
                Here is an overview of your school management system.
            </p>

        </section>


        <!-- Statistics -->
        <div class="stats">

            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👨‍🏫</div>
                <h3><?php echo $total_teachers; ?></h3>
                <p>Total Teachers</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👨‍👩‍👧</div>
                <h3>0</h3>
                <p>Total Parents</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <h3>0</h3>
                <p>Total Subjects</p>
            </div>

        </div>


        <!-- Management -->
        <div class="section-title">
            <h2>School Management</h2>
            <p>Manage different sections of your school.</p>
        </div>

        <div class="cards">

            <a href="../admin/students/index.php" class="card">
                <div class="card-icon">🎓</div>
                <h3>Students</h3>
                <p>Add, edit, view and manage student information.</p>
            </a>

            <a href="../admin/teachers/index.php" class="card">
                <div class="card-icon">👨‍🏫</div>
                <h3>Teachers</h3>
                <p>Manage teachers, employee information and specialization.</p>
            </a>

            <a href="#" class="card">
                <div class="card-icon">👨‍👩‍👧</div>
                <h3>Parents</h3>
                <p>Manage parent information and student relationships.</p>
            </a>

            <a href="#" class="card">
                <div class="card-icon">📚</div>
                <h3>Subjects</h3>
                <p>Create and manage school subjects.</p>
            </a>

            <a href="#" class="card">
                <div class="card-icon">🏫</div>
                <h3>Classes</h3>
                <p>Manage classes and classroom information.</p>
            </a>

            <a href="#" class="card">
                <div class="card-icon">📋</div>
                <h3>Attendance</h3>
                <p>Monitor and manage student attendance.</p>
            </a>

            <a href="#" class="card">
                <div class="card-icon">📊</div>
                <h3>Results</h3>
                <p>Manage student academic results and performance.</p>
            </a>

            <a href="#" class="card">
                <div class="card-icon">⚙️</div>
                <h3>Settings</h3>
                <p>Configure system settings and administration options.</p>
            </a>

        </div>

    </main>

</body>
</html>