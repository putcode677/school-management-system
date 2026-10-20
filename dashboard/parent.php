<?php

session_start();

require_once "../config/database.php";

/* ==============================
   AUTHENTICATION
============================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION["role"] !== "parent") {
    header("Location: ../auth/login.php");
    exit;
}


/* ==============================
   PARENT DATA
============================== */

$user_id = $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT
        parents.id,
        parents.parent_number,
        parents.phone,
        parents.address,
        parents.occupation,
        users.full_name,
        users.email,
        users.status
    FROM parents
    INNER JOIN users
        ON parents.user_id = users.id
    WHERE parents.user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$parent = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parent) {
    die("Parent account information was not found.");
}


/* ==============================
   CHILDREN
============================== */

$children_stmt = $pdo->prepare("
    SELECT
        students.id,
        students.student_number,
        students.date_of_birth,
        students.gender,
        students.phone,
        students.address,
        students.admission_date,
        users.full_name,
        users.email,
        classes.class_name,
        classes.class_code,
        student_parents.relationship
    FROM student_parents
    INNER JOIN students
        ON student_parents.student_id = students.id
    INNER JOIN users
        ON students.user_id = users.id
    LEFT JOIN student_classes
        ON student_classes.student_id = students.id
    LEFT JOIN classes
        ON classes.id = student_classes.class_id
    WHERE student_parents.parent_id = ?
    ORDER BY users.full_name ASC
");

$children_stmt->execute([$parent["id"]]);

$children = $children_stmt->fetchAll(PDO::FETCH_ASSOC);

$children_count = count($children);


/* ==============================
   INITIALS
============================== */

$name_parts = explode(" ", trim($parent["full_name"]));

$initials = "";

foreach ($name_parts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    if (strlen($initials) >= 2) {
        break;
    }
}


/* ==============================
   CHILD INITIALS FUNCTION
============================== */

function getInitials($name)
{
    $parts = explode(" ", trim($name));

    $initials = "";

    foreach ($parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Parent Dashboard | School Management System</title>


<style>

/* =====================================================
   NEUMORPHISM (SOFT UI)
   PARENT DASHBOARD
   — Soft shadows, raised/inset elements
   — Same color background + elements
   — 3D depth via dual shadows
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


:root {

    /* Soft blue-gray palette */
    --bg: #e0e5ec;

    --text-dark: #2d3748;
    --text-mid: #4a5568;
    --text-light: #718096;

    --shadow-dark: rgba(163, 177, 198, 0.6);
    --shadow-light: rgba(255, 255, 255, 0.8);

    --accent: #667eea;
    --accent-dark: #5568d3;
    --accent-light: #7c93f5;

    --success: #48bb78;
    --warning: #ed8936;
    --danger: #f56565;

}


/* =====================================================
   BODY — soft background
===================================================== */

body {

    font-family:
        "Segoe UI",
        "Roboto",
        "Helvetica Neue",
        Arial,
        sans-serif;

    min-height: 100vh;

    color: var(--text-dark);

    overflow-x: hidden;

    background: var(--bg);

}


/* =====================================================
   SIDEBAR — raised panel
===================================================== */

.sidebar {

    position: fixed;

    top: 20px;
    left: 20px;
    bottom: 20px;

    width: 260px;

    padding: 25px 18px;

    border-radius: 30px;

    background: var(--bg);

    box-shadow:

        /* Raised effect */
        9px 9px 16px var(--shadow-dark),
        -9px -9px 16px var(--shadow-light);

    z-index: 100;

}


/* =====================================================
   LOGO — inset text
===================================================== */

.logo {

    padding: 5px 10px 30px;

    text-align: center;

}


.logo h2 {

    font-size: 22px;

    font-weight: 700;

    letter-spacing: .5px;

    color: var(--text-dark);

    /* Inset text effect */
    text-shadow:

        1px 1px 2px var(--shadow-light),
        -1px -1px 2px var(--shadow-dark);

}


.logo span {

    color: var(--accent);

}


/* =====================================================
   NAVIGATION — soft buttons
===================================================== */

.nav {

    display: flex;

    flex-direction: column;

    gap: 10px;

}


.nav a {

    position: relative;

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 13px 15px;

    color: var(--text-mid);

    text-decoration: none;

    font-size: 14px;

    font-weight: 500;

    border-radius: 18px;

    background: var(--bg);

    box-shadow:

        /* Raised button */
        5px 5px 10px var(--shadow-dark),
        -5px -5px 10px var(--shadow-light);

    transition: all .25s ease;

}


.nav a:hover {

    color: var(--accent);

    box-shadow:

        /* Slightly raised */
        7px 7px 14px var(--shadow-dark),
        -7px -7px 14px var(--shadow-light);

}


.nav a:active {

    box-shadow:

        /* Pressed in */
        inset 5px 5px 10px var(--shadow-dark),
        inset -5px -5px 10px var(--shadow-light);

}


.nav a.active {

    color: var(--accent);

    /* Pressed in effect */
    box-shadow:

        inset 5px 5px 10px var(--shadow-dark),
        inset -5px -5px 10px var(--shadow-light);

}


.nav a.active::before {

    content: "";

    position: absolute;

    left: -2px;

    top: 25%;

    width: 4px;

    height: 50%;

    border-radius: 10px;

    background: var(--accent);

    box-shadow: 0 0 10px var(--accent);

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 300px;

    padding: 28px 32px 50px;

    max-width: 1600px;

}


/* =====================================================
   TOP BAR
===================================================== */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 28px;

}


.topbar h1 {

    font-size: 28px;

    font-weight: 700;

    color: var(--text-dark);

    /* Inset text */
    text-shadow:

        2px 2px 4px var(--shadow-light),
        -2px -2px 4px var(--shadow-dark);

}


.topbar p {

    margin-top: 5px;

    color: var(--text-light);

    font-size: 14px;

}


.user-box {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 10px 18px 10px 10px;

    border-radius: 25px;

    background: var(--bg);

    box-shadow:

        /* Raised */
        6px 6px 12px var(--shadow-dark),
        -6px -6px 12px var(--shadow-light);

}


.avatar {

    width: 45px;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: var(--bg);

    color: var(--accent);

    font-size: 15px;

    font-weight: 700;

    /* Inset circle */
    box-shadow:

        inset 4px 4px 8px var(--shadow-dark),
        inset -4px -4px 8px var(--shadow-light);

}


.user-box strong {

    font-size: 14px;

    color: var(--text-dark);

}


.user-box small {

    display: block;

    margin-top: 2px;

    color: var(--text-light);

    font-size: 12px;

}


/* =====================================================
   NEUMORPHIC CARD
===================================================== */

.card {

    position: relative;

    overflow: hidden;

    background: var(--bg);

    border-radius: 25px;

    box-shadow:

        /* Raised card */
        8px 8px 16px var(--shadow-dark),
        -8px -8px 16px var(--shadow-light);

    transition: all .3s ease;

}


.card:hover {

    transform: translateY(-3px);

    box-shadow:

        /* More raised */
        12px 12px 24px var(--shadow-dark),
        -12px -12px 24px var(--shadow-light);

}


/* =====================================================
   WELCOME
===================================================== */

.welcome {

    min-height: 190px;

    padding: 32px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;

}


.welcome-content {

    position: relative;

    z-index: 2;

}


.welcome h2 {

    font-size: 30px;

    margin-bottom: 9px;

    color: var(--text-dark);

    /* Inset text */
    text-shadow:

        2px 2px 4px var(--shadow-light),
        -2px -2px 4px var(--shadow-dark);

}


.welcome p {

    color: var(--text-light);

    font-size: 15px;

}


/* Soft circle */
.welcome-bubble {

    width: 110px;

    height: 110px;

    border-radius: 50%;

    background: var(--bg);

    /* Inset circle */
    box-shadow:

        inset 8px 8px 16px var(--shadow-dark),
        inset -8px -8px 16px var(--shadow-light);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 40px;

    color: var(--accent);

}


.welcome-bubble::before {

    content: "👨‍👩‍👧";

}


/* =====================================================
   PROFILE
===================================================== */

.profile-grid {

    display: grid;

    grid-template-columns: 1.3fr 1fr 1fr 1fr;

    gap: 20px;

    margin-bottom: 25px;

}


.profile-card {

    padding: 23px;

}


.profile-main {

    display: flex;

    align-items: center;

    gap: 17px;

}


.profile-avatar {

    width: 72px;

    height: 72px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: var(--bg);

    color: var(--accent);

    font-size: 24px;

    font-weight: 700;

    /* Inset circle */
    box-shadow:

        inset 6px 6px 12px var(--shadow-dark),
        inset -6px -6px 12px var(--shadow-light);

}


.profile-card h3 {

    font-size: 17px;

    margin-bottom: 5px;

    color: var(--text-dark);

}


.profile-card p {

    color: var(--text-light);

    font-size: 13px;

    line-height: 1.7;

}


/* =====================================================
   SECTION
===================================================== */

.section-title {

    margin: 30px 0 15px;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.section-title h2 {

    font-size: 20px;

    color: var(--text-dark);

    /* Inset text */
    text-shadow:

        2px 2px 4px var(--shadow-light),
        -2px -2px 4px var(--shadow-dark);

}


.section-title span {

    color: var(--text-light);

    font-size: 13px;

}


/* =====================================================
   STATS
===================================================== */

.stats {

    display: grid;

    grid-template-columns: repeat(4, 1fr);

    gap: 20px;

}


.stat {

    padding: 21px;

    display: flex;

    align-items: center;

    gap: 15px;

}


.stat-icon {

    width: 54px;

    height: 54px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: var(--bg);

    font-size: 22px;

    color: var(--accent);

    /* Inset circle */
    box-shadow:

        inset 4px 4px 8px var(--shadow-dark),
        inset -4px -4px 8px var(--shadow-light);

}


.stat h3 {

    font-size: 26px;

    margin-bottom: 3px;

    color: var(--text-dark);

    /* Inset text */
    text-shadow:

        1px 1px 2px var(--shadow-light),
        -1px -1px 2px var(--shadow-dark);

}


.stat p {

    color: var(--text-light);

    font-size: 12px;

}


/* =====================================================
   CHILDREN
===================================================== */

.children-grid {

    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 20px;

}


.child-card {

    padding: 23px;

}


.child-header {

    display: flex;

    align-items: center;

    gap: 14px;

    margin-bottom: 20px;

}


.child-avatar {

    width: 58px;

    height: 58px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: var(--bg);

    color: var(--accent);

    font-size: 16px;

    font-weight: 700;

    /* Inset circle */
    box-shadow:

        inset 4px 4px 8px var(--shadow-dark),
        inset -4px -4px 8px var(--shadow-light);

}


.child-info h3 {

    font-size: 16px;

    margin-bottom: 4px;

    color: var(--text-dark);

}


.child-info p {

    color: var(--text-light);

    font-size: 12px;

}


.child-details {

    padding-top: 15px;

    border-top: 1px solid rgba(163, 177, 198, 0.3);

}


.detail {

    display: flex;

    justify-content: space-between;

    padding: 8px 0;

    font-size: 13px;

}


.detail span:first-child {

    color: var(--text-light);

}


.detail span:last-child {

    color: var(--text-dark);

    font-weight: 600;

}


/* =====================================================
   SERVICES
===================================================== */

.services-grid {

    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 20px;

}


.service {

    display: block;

    padding: 23px;

    color: var(--text-dark);

    text-decoration: none;

}


.service-icon {

    width: 54px;

    height: 54px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 16px;

    border-radius: 50%;

    background: var(--bg);

    font-size: 22px;

    color: var(--accent);

    /* Inset circle */
    box-shadow:

        inset 4px 4px 8px var(--shadow-dark),
        inset -4px -4px 8px var(--shadow-light);

}


.service h3 {

    font-size: 16px;

    margin-bottom: 7px;

    color: var(--text-dark);

}


.service p {

    color: var(--text-light);

    font-size: 13px;

    line-height: 1.6;

}


/* =====================================================
   EMPTY CHILDREN
===================================================== */

.empty {

    padding: 55px 25px;

    text-align: center;

}


.empty-icon {

    width: 72px;

    height: 72px;

    margin: 0 auto 17px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: var(--bg);

    font-size: 28px;

    color: var(--accent);

    /* Inset circle */
    box-shadow:

        inset 6px 6px 12px var(--shadow-dark),
        inset -6px -6px 12px var(--shadow-light);

}


.empty h3 {

    margin-bottom: 8px;

    color: var(--text-dark);

}


.empty p {

    color: var(--text-light);

    font-size: 13px;

}


/* =====================================================
   LOGOUT — pressed button
===================================================== */

.logout {

    margin-top: 25px;

}


.logout a {

    display: block;

    padding: 12px;

    text-align: center;

    text-decoration: none;

    color: var(--danger);

    font-size: 14px;

    font-weight: 600;

    border-radius: 18px;

    background: var(--bg);

    /* Pressed in effect */
    box-shadow:

        inset 5px 5px 10px var(--shadow-dark),
        inset -5px -5px 10px var(--shadow-light);

    transition: all .25s ease;

}


.logout a:hover {

    color: var(--text-dark);

    box-shadow:

        /* Raised on hover */
        5px 5px 10px var(--shadow-dark),
        -5px -5px 10px var(--shadow-light);

}


.logout a:active {

    box-shadow:

        /* More pressed */
        inset 7px 7px 14px var(--shadow-dark),
        inset -7px -7px 14px var(--shadow-light);

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1200px) {

    .profile-grid {

        grid-template-columns: 1fr 1fr;

    }

    .profile-grid
    .profile-card:first-child {

        grid-column: span 2;

    }

    .stats {

        grid-template-columns: repeat(2, 1fr);

    }

    .children-grid,
    .services-grid {

        grid-template-columns: repeat(2, 1fr);

    }

}


@media (max-width: 850px) {

    .sidebar {

        position: relative;

        top: 0;

        left: 0;

        bottom: auto;

        width: calc(100% - 30px);

        height: auto;

        margin: 15px;

    }


    .main {

        margin-left: 0;

        padding: 20px 15px 40px;

    }


    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;

    }


    .welcome {

        flex-direction: column;

        align-items: flex-start;

        gap: 25px;

    }


    .profile-grid,
    .stats,
    .children-grid,
    .services-grid {

        grid-template-columns: 1fr;

    }


    .profile-grid
    .profile-card:first-child {

        grid-column: auto;

    }

}


@media (max-width: 500px) {

    .main {

        padding: 15px 10px 35px;

    }


    .welcome {

        padding: 24px;

    }


    .welcome h2 {

        font-size: 24px;

    }


    .user-box {

        width: 100%;

    }

}

</style>


<!-- =====================================================
     DASHBOARD
===================================================== -->

<div class="sidebar">

    <div class="logo">

        <h2>
            Parent<span>Portal</span>
        </h2>

    </div>


    <div class="nav">

        <a href="#" class="active">
            🏠
            <span>Dashboard</span>
        </a>

        <a href="#">
            👨‍👩‍👧
            <span>My Children</span>
        </a>

        <a href="#">
            📊
            <span>Results</span>
        </a>

        <a href="#">
            📈
            <span>Performance</span>
        </a>

        <a href="#">
            🕒
            <span>Attendance</span>
        </a>

        <a href="#">
            📢
            <span>School Updates</span>
        </a>

        <a href="#">
            💬
            <span>Feedback</span>
        </a>

        <a href="#">
            👤
            <span>My Profile</span>
        </a>

    </div>


    <div class="logout">

        <a href="../auth/logout.php">
            🚪 Logout
        </a>

    </div>

</div>


<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h1>
                Parent Dashboard
            </h1>

            <p>
                Welcome back to your family portal
            </p>

        </div>


        <div class="user-box">

            <div class="avatar">
                <?= htmlspecialchars($initials) ?>
            </div>

            <div>

                <strong>
                    <?= htmlspecialchars($parent["full_name"]) ?>
                </strong>

                <small>
                    Parent
                </small>

            </div>

        </div>

    </div>


    <!-- WELCOME -->

    <div class="card welcome">

        <div class="welcome-content">

            <h2>
                Welcome,
                <?= htmlspecialchars($parent["full_name"]) ?> 👋
            </h2>

            <p>
                Stay connected with your children's
                academic journey and school activities.
            </p>

        </div>


        <div class="welcome-bubble"></div>

    </div>


    <!-- PROFILE -->

    <div class="profile-grid">


        <div class="card profile-card">

            <div class="profile-main">

                <div class="profile-avatar">

                    <?= htmlspecialchars($initials) ?>

                </div>

                <div>

                    <h3>
                        <?= htmlspecialchars($parent["full_name"]) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($parent["email"]) ?>
                    </p>

                </div>

            </div>

        </div>


        <div class="card profile-card">

            <h3>
                📞 Phone
            </h3>

            <p>
                <?= !empty($parent["phone"])
                    ? htmlspecialchars($parent["phone"])
                    : "Not provided"
                ?>
            </p>

        </div>


        <div class="card profile-card">

            <h3>
                🎫 Login ID
            </h3>

            <p>
                <?= !empty($parent["parent_number"])
                    ? htmlspecialchars($parent["parent_number"])
                    : "Not provided"
                ?>
            </p>

        </div>


        <div class="card profile-card">

            <h3>
                💼 Occupation
            </h3>

            <p>
                <?= !empty($parent["occupation"])
                    ? htmlspecialchars($parent["occupation"])
                    : "Not provided"
                ?>
            </p>

        </div>

    </div>


    <!-- FAMILY OVERVIEW -->

    <div class="section-title">

        <h2>
            Family Overview
        </h2>

        <span>
            Your family at a glance
        </span>

    </div>


    <div class="stats">


        <div class="card stat">

            <div class="stat-icon">
                👨‍👩‍👧
            </div>

            <div>

                <h3>
                    <?= $children_count ?>
                </h3>

                <p>
                    My Children
                </p>

            </div>

        </div>


        <div class="card stat">

            <div class="stat-icon">
                📊
            </div>

            <div>

                <h3>
                    0
                </h3>

                <p>
                    Results
                </p>

            </div>

        </div>


        <div class="card stat">

            <div class="stat-icon">
                🕒
            </div>

            <div>

                <h3>
                    0%
                </h3>

                <p>
                    Attendance
                </p>

            </div>

        </div>


        <div class="card stat">

            <div class="stat-icon">
                📢
            </div>

            <div>

                <h3>
                    0
                </h3>

                <p>
                    School Updates
                </p>

            </div>

        </div>

    </div>


    <!-- CHILDREN -->

    <div class="section-title">

        <h2>
            My Children
        </h2>

        <span>
            <?= $children_count ?> linked
        </span>

    </div>


    <?php if ($children_count > 0): ?>

        <div class="children-grid">


            <?php foreach ($children as $child): ?>

                <div class="card child-card">

                    <div class="child-header">

                        <div class="child-avatar">

                            <?= htmlspecialchars(
                                getInitials($child["full_name"])
                            ) ?>

                        </div>


                        <div class="child-info">

                            <h3>
                                <?= htmlspecialchars(
                                    $child["full_name"]
                                ) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $child["student_number"]
                                ) ?>
                            </p>

                        </div>

                    </div>


                    <div class="child-details">


                        <div class="detail">

                            <span>
                                Email
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $child["email"]
                                ) ?>
                            </span>

                        </div>


                        <div class="detail">

                            <span>
                                Class
                            </span>

                            <span>
                                <?= !empty($child["class_name"])
                                    ? htmlspecialchars($child["class_name"])
                                        . " (" . htmlspecialchars($child["class_code"]) . ")"
                                    : "Not assigned"
                                ?>
                            </span>

                        </div>


                        <div class="detail">

                            <span>
                                Relationship
                            </span>

                            <span>
                                <?= !empty($child["relationship"])
                                    ? htmlspecialchars($child["relationship"])
                                    : "Not specified"
                                ?>
                            </span>

                        </div>


                        <div class="detail">

                            <span>
                                Gender
                            </span>

                            <span>
                                <?= !empty($child["gender"])
                                    ? htmlspecialchars($child["gender"])
                                    : "Not provided"
                                ?>
                            </span>

                        </div>


                        <div class="detail">

                            <span>
                                Phone
                            </span>

                            <span>
                                <?= !empty($child["phone"])
                                    ? htmlspecialchars($child["phone"])
                                    : "Not provided"
                                ?>
                            </span>

                        </div>


                    </div>

                </div>

            <?php endforeach; ?>


        </div>

    <?php else: ?>


        <div class="card empty">

            <div class="empty-icon">
                👨‍👩‍👧
            </div>

            <h3>
                No Children Linked Yet
            </h3>

            <p>
                Your children will appear here once
                the school administrator links them
                to your parent account.
            </p>

        </div>

    <?php endif; ?>


    <!-- SERVICES -->

    <div class="section-title">

        <h2>
            Parent Services
        </h2>

        <span>
            Quick access
        </span>

    </div>


    <div class="services-grid">


        <a href="#" class="card service">

            <div class="service-icon">
                👨‍👩‍👧
            </div>

            <h3>
                My Children
            </h3>

            <p>
                View your children's profiles
                and school information.
            </p>

        </a>


        <a href="#" class="card service">

            <div class="service-icon">
                📊
            </div>

            <h3>
                Academic Results
            </h3>

            <p>
                View examination results
                for your children.
            </p>

        </a>


        <a href="#" class="card service">

            <div class="service-icon">
                📈
            </div>

            <h3>
                Performance
            </h3>

            <p>
                Track academic performance
                across examinations.
            </p>

        </a>


        <a href="#" class="card service">

            <div class="service-icon">
                🕒
            </div>

            <h3>
                Attendance
            </h3>

            <p>
                Monitor your children's
                school attendance.
            </p>

        </a>


        <a href="#" class="card service">

            <div class="service-icon">
                📢
            </div>

            <h3>
                School Updates
            </h3>

            <p>
                Stay informed about meetings,
                announcements and events.
            </p>

        </a>


        <a href="#" class="card service">

            <div class="service-icon">
                💬
            </div>

            <h3>
                Feedback
            </h3>

            <p>
                Send comments and communicate
                with the school.
            </p>

        </a>


    </div>


</div>

</body>

</html>