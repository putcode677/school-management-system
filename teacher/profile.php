<?php

session_start();

require_once "../config/database.php";

// CHECK LOGIN
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// CHECK TEACHER ROLE
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "teacher") {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

try {

    $stmt = $pdo->prepare(
        "SELECT
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
$phone = $teacher["phone"] ?: "Not provided";
$address = $teacher["address"] ?: "Not provided";
$specialization = $teacher["specialization"] ?: "Not specified";
$status = $teacher["status"] ?: "active";

$hire_date = "Not provided";

if (!empty($teacher["hire_date"])) {
    $hire_date = date("d M Y", strtotime($teacher["hire_date"]));
}

// INITIALS
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

<title>My Profile</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    min-height: 100vh;
    background: #dbeafe;
    color: #243b53;
}

.container {
    max-width: 1000px;
    margin: auto;
    padding: 40px 25px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.header h1 {
    color: #1e3a5f;
    font-size: 30px;
}

.header p {
    margin-top: 6px;
    color: #6b829c;
}

.back {
    padding: 13px 20px;
    border-radius: 15px;
    text-decoration: none;
    color: #1769aa;
    font-weight: 600;
    background: #dbeafe;
    box-shadow:
        7px 7px 14px #b8c9dc,
        -7px -7px 14px #ffffff;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 22px;
    padding: 30px;
    margin-bottom: 25px;
    border-radius: 25px;
    background: #dbeafe;
    box-shadow:
        10px 10px 20px #b8c9dc,
        -10px -10px 20px #ffffff;
}

.avatar {
    width: 85px;
    height: 85px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #1769aa;
    font-size: 25px;
    font-weight: bold;
    background: #dbeafe;
    box-shadow:
        inset 6px 6px 12px #b8c9dc,
        inset -6px -6px 12px #ffffff;
}

.profile-header h2 {
    color: #1e3a5f;
    margin-bottom: 6px;
}

.profile-header p {
    color: #6b829c;
}

.status {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 13px;
    border-radius: 10px;
    color: #1769aa;
    font-size: 12px;
    font-weight: bold;
    text-transform: capitalize;
    box-shadow:
        inset 3px 3px 6px #b8c9dc,
        inset -3px -3px 6px #ffffff;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 22px;
}

.card {
    padding: 25px;
    border-radius: 20px;
    background: #dbeafe;
    box-shadow:
        8px 8px 18px #b8c9dc,
        -8px -8px 18px #ffffff;
}

.card-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    border-radius: 15px;
    font-size: 22px;
    box-shadow:
        inset 5px 5px 10px #b8c9dc,
        inset -5px -5px 10px #ffffff;
}

.card small {
    display: block;
    color: #6b829c;
    font-size: 12px;
    margin-bottom: 7px;
}

.card strong {
    color: #1e3a5f;
    font-size: 16px;
    word-break: break-word;
}

@media (max-width: 650px) {

    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }

    .back {
        width: 100%;
        text-align: center;
    }

    .profile-header {
        flex-direction: column;
        text-align: center;
    }

    .profile-grid {
        grid-template-columns: 1fr;
    }
}

</style>

</head>

<body>

<div class="container">

    <div class="header">

        <div>
            <h1>My Profile</h1>
            <p>View your teacher account information.</p>
        </div>

        <a href="../dashboard/teacher.php" class="back">
            ← Back to Dashboard
        </a>

    </div>


    <div class="profile-header">

        <div class="avatar">
            <?= htmlspecialchars($initials) ?>
        </div>

        <div>

            <h2>
                <?= htmlspecialchars($full_name) ?>
            </h2>

            <p>
                <?= htmlspecialchars($email) ?>
            </p>

            <span class="status">
                <?= htmlspecialchars($status) ?>
            </span>

        </div>

    </div>


    <div class="profile-grid">


        <div class="card">

            <div class="card-icon">🆔</div>

            <small>Employee Number</small>

            <strong>
                <?= htmlspecialchars($employee_number) ?>
            </strong>

        </div>


        <div class="card">

            <div class="card-icon">📧</div>

            <small>Email Address</small>

            <strong>
                <?= htmlspecialchars($email) ?>
            </strong>

        </div>


        <div class="card">

            <div class="card-icon">📱</div>

            <small>Phone</small>

            <strong>
                <?= htmlspecialchars($phone) ?>
            </strong>

        </div>


        <div class="card">

            <div class="card-icon">🏠</div>

            <small>Address</small>

            <strong>
                <?= htmlspecialchars($address) ?>
            </strong>

        </div>


        <div class="card">

            <div class="card-icon">🔬</div>

            <small>Specialization</small>

            <strong>
                <?= htmlspecialchars($specialization) ?>
            </strong>

        </div>


        <div class="card">

            <div class="card-icon">📅</div>

            <small>Hire Date</small>

            <strong>
                <?= htmlspecialchars($hire_date) ?>
            </strong>

        </div>


    </div>

</div>

</body>

</html>
