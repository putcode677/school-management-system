<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

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

        .header {
            background: #343a40;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .logout {
            color: white;
            text-decoration: none;
            background: #dc3545;
            padding: 10px 16px;
            border-radius: 6px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .welcome h2 {
            margin-bottom: 10px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .card h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .card p {
            color: #777;
            margin-bottom: 15px;
        }

        .card a {
            display: inline-block;
            text-decoration: none;
            background: #007bff;
            color: white;
            padding: 9px 14px;
            border-radius: 5px;
        }

        .card a:hover {
            background: #0056b3;
        }

    </style>

</head>

<body>

<div class="header">

    <h1>School Management System</h1>

    <a
        href="../auth/logout.php"
        class="logout"
    >
        Logout
    </a>

</div>

<div class="container">

    <div class="welcome">

        <h2>
            Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
        </h2>

        <p>
            You are logged in as Administrator.
        </p>

    </div>

    <div class="cards">

        <div class="card">

            <h3>Students</h3>

            <p>
                Manage student accounts and information.
            </p>

            <a href="#">
                Manage Students
            </a>

        </div>

        <div class="card">

            <h3>Teachers</h3>

            <p>
                Manage teachers and lecturer information.
            </p>

            <a href="#">
                Manage Teachers
            </a>

        </div>

        <div class="card">

            <h3>Subjects</h3>

            <p>
                Add, edit and manage school subjects.
            </p>

            <a href="../admin/subjects/">
                Manage Subjects
            </a>

        </div>

        <div class="card">

            <h3>Classes</h3>

            <p>
                Manage classes and programs.
            </p>

            <a href="#">
                Manage Classes
            </a>

        </div>

        <div class="card">

            <h3>Reports</h3>

            <p>
                View school management reports.
            </p>

            <a href="#">
                View Reports
            </a>

        </div>

        <div class="card">

            <h3>System Users</h3>

            <p>
                Manage system users and roles.
            </p>

            <a href="#">
                Manage Users
            </a>

        </div>

    </div>

</div>

</body>

</html>