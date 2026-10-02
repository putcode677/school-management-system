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
        users.email
    FROM student_parents
    INNER JOIN students
        ON student_parents.student_id = students.id
    INNER JOIN users
        ON students.user_id = users.id
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
   LIQUIDMORPHISM
   PARENT DASHBOARD
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


:root {

    --blue: #38bdf8;
    --cyan: #22d3ee;
    --purple: #8b5cf6;
    --pink: #ec4899;

    --dark: #071329;

    --text: #ffffff;

    --muted: rgba(255,255,255,.65);

    --glass: rgba(255,255,255,.09);

    --border: rgba(255,255,255,.18);

}


/* =====================================================
   BODY
===================================================== */

body {

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    min-height: 100vh;

    color: var(--text);

    overflow-x: hidden;

    background:

        radial-gradient(
            circle at 10% 15%,
            rgba(34,211,238,.30),
            transparent 28%
        ),

        radial-gradient(
            circle at 90% 10%,
            rgba(139,92,246,.28),
            transparent 30%
        ),

        radial-gradient(
            circle at 80% 85%,
            rgba(236,72,153,.18),
            transparent 30%
        ),

        linear-gradient(
            135deg,
            #061225,
            #0a1d3a,
            #111a42
        );

    background-attachment: fixed;
}


/* =====================================================
   LIQUID BLOBS
===================================================== */

body::before {

    content: "";

    position: fixed;

    width: 380px;
    height: 380px;

    top: -120px;
    left: 300px;

    border-radius:
        42% 58% 63% 37%
        / 45% 39% 61% 55%;

    background:
        linear-gradient(
            135deg,
            rgba(34,211,238,.32),
            rgba(59,130,246,.15)
        );

    filter: blur(2px);

    animation:
        liquidOne 12s ease-in-out infinite;

    pointer-events: none;

    z-index: -1;
}


body::after {

    content: "";

    position: fixed;

    width: 330px;
    height: 330px;

    right: -100px;
    bottom: -80px;

    border-radius:
        60% 40% 35% 65%
        / 50% 55% 45% 50%;

    background:
        linear-gradient(
            135deg,
            rgba(139,92,246,.35),
            rgba(236,72,153,.12)
        );

    filter: blur(3px);

    animation:
        liquidTwo 14s ease-in-out infinite;

    pointer-events: none;

    z-index: -1;
}


@keyframes liquidOne {

    0%,100% {
        transform:
            translate(0,0)
            rotate(0deg)
            scale(1);
    }

    50% {
        transform:
            translate(80px,50px)
            rotate(25deg)
            scale(1.12);
    }
}


@keyframes liquidTwo {

    0%,100% {
        transform:
            translate(0,0)
            rotate(0deg)
            scale(1);
    }

    50% {
        transform:
            translate(-70px,-50px)
            rotate(-25deg)
            scale(1.15);
    }
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    top: 18px;
    left: 18px;
    bottom: 18px;

    width: 255px;

    padding: 25px 16px;

    border-radius: 32px;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,.14),
            rgba(255,255,255,.045)
        );

    border:
        1px solid
        rgba(255,255,255,.20);

    backdrop-filter:
        blur(25px)
        saturate(160%);

    -webkit-backdrop-filter:
        blur(25px)
        saturate(160%);

    box-shadow:

        0 30px 70px
        rgba(0,0,0,.30),

        inset 0 1px 0
        rgba(255,255,255,.25);

    z-index: 100;
}


/* =====================================================
   LOGO
===================================================== */

.logo {

    padding:
        5px
        10px
        30px;

}


.logo h2 {

    font-size: 21px;

    font-weight: 700;

    letter-spacing: .5px;

}


.logo span {

    color: var(--cyan);

}


/* =====================================================
   NAVIGATION
===================================================== */

.nav {

    display: flex;

    flex-direction: column;

    gap: 8px;

}


.nav a {

    position: relative;

    display: flex;

    align-items: center;

    gap: 13px;

    padding:
        13px
        15px;

    color:
        rgba(255,255,255,.67);

    text-decoration: none;

    border-radius: 17px;

    border:
        1px solid
        transparent;

    transition:
        all .3s ease;

}


.nav a:hover {

    color: #fff;

    background:
        rgba(255,255,255,.08);

    transform:
        translateX(4px);

}


.nav a.active {

    color: #fff;

    background:

        linear-gradient(
            135deg,
            rgba(34,211,238,.20),
            rgba(139,92,246,.18)
        );

    border:
        1px solid
        rgba(255,255,255,.18);

    box-shadow:

        0 10px 30px
        rgba(0,0,0,.18),

        inset 0 1px 0
        rgba(255,255,255,.15);

}


.nav a.active::before {

    content: "";

    position: absolute;

    left: -1px;

    top: 20%;

    width: 3px;

    height: 60%;

    border-radius: 10px;

    background:
        linear-gradient(
            #22d3ee,
            #8b5cf6
        );

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 291px;

    padding:
        28px
        32px
        50px;

    max-width: 1600px;

}


/* =====================================================
   TOP BAR
===================================================== */

.topbar {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom:
        28px;

}


.topbar h1 {

    font-size:
        27px;

    font-weight:
        700;

}


.topbar p {

    margin-top:
        5px;

    color:
        var(--muted);

    font-size:
        14px;

}


.user-box {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    padding:
        8px
        15px
        8px
        8px;

    border-radius:
        20px;

    background:
        rgba(255,255,255,.08);

    border:
        1px solid
        rgba(255,255,255,.16);

    backdrop-filter:
        blur(20px);

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,.12);

}


.avatar {

    width:
        43px;

    height:
        43px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        16px;

    background:

        linear-gradient(
            135deg,
            #06b6d4,
            #6366f1
        );

    font-size:
        14px;

    font-weight:
        700;

    box-shadow:
        0 10px 25px
        rgba(34,211,238,.25);

}


.user-box strong {

    font-size:
        14px;

}


.user-box small {

    display:
        block;

    margin-top:
        2px;

    color:
        var(--muted);

}


/* =====================================================
   LIQUID CARD
===================================================== */

.card {

    position:
        relative;

    overflow:
        hidden;

    background:

        linear-gradient(
            145deg,
            rgba(255,255,255,.12),
            rgba(255,255,255,.045)
        );

    border:
        1px solid
        rgba(255,255,255,.17);

    border-radius:
        28px;

    backdrop-filter:
        blur(25px)
        saturate(160%);

    -webkit-backdrop-filter:
        blur(25px)
        saturate(160%);

    box-shadow:

        0 25px 55px
        rgba(0,0,0,.18),

        inset 0 1px 0
        rgba(255,255,255,.20);

    transition:
        transform .35s ease,
        box-shadow .35s ease,
        background .35s ease;

}


.card::before {

    content:
        "";

    position:
        absolute;

    width:
        180px;

    height:
        180px;

    right:
        -100px;

    top:
        -100px;

    border-radius:
        50%;

    background:
        rgba(34,211,238,.10);

    filter:
        blur(10px);

    pointer-events:
        none;

}


.card:hover {

    transform:
        translateY(-4px);

    background:

        linear-gradient(
            145deg,
            rgba(255,255,255,.15),
            rgba(255,255,255,.055)
        );

    box-shadow:

        0 30px 65px
        rgba(0,0,0,.24),

        inset 0 1px 0
        rgba(255,255,255,.22);

}


/* =====================================================
   WELCOME
===================================================== */

.welcome {

    min-height:
        190px;

    padding:
        32px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom:
        25px;

}


.welcome-content {

    position:
        relative;

    z-index:
        2;

}


.welcome h2 {

    font-size:
        31px;

    margin-bottom:
        9px;

}


.welcome p {

    color:
        var(--muted);

    font-size:
        15px;

}


.welcome-bubble {

    width:
        105px;

    height:
        105px;

    border-radius:
        43% 57% 55% 45%
        / 55% 42% 58% 45%;

    background:

        linear-gradient(
            135deg,
            rgba(34,211,238,.35),
            rgba(139,92,246,.30)
        );

    border:
        1px solid
        rgba(255,255,255,.20);

    box-shadow:

        inset 10px 10px 25px
        rgba(255,255,255,.10),

        0 20px 40px
        rgba(0,0,0,.20);

    animation:
        bubble 7s ease-in-out infinite;

}


@keyframes bubble {

    0%,100% {

        border-radius:
            43% 57% 55% 45%
            / 55% 42% 58% 45%;

        transform:
            rotate(0deg);
    }

    50% {

        border-radius:
            60% 40% 35% 65%
            / 45% 60% 40% 55%;

        transform:
            rotate(12deg)
            translateY(-8px);
    }

}


/* =====================================================
   PROFILE
===================================================== */

.profile-grid {

    display:
        grid;

    grid-template-columns:
        1.3fr
        1fr
        1fr;

    gap:
        18px;

    margin-bottom:
        25px;

}


.profile-card {

    padding:
        23px;

}


.profile-main {

    display:
        flex;

    align-items:
        center;

    gap:
        17px;

}


.profile-avatar {

    width:
        70px;

    height:
        70px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        24px;

    background:

        linear-gradient(
            135deg,
            #06b6d4,
            #6366f1,
            #8b5cf6
        );

    font-size:
        24px;

    font-weight:
        700;

    box-shadow:

        0 15px 35px
        rgba(99,102,241,.25);

}


.profile-card h3 {

    font-size:
        17px;

    margin-bottom:
        5px;

}


.profile-card p {

    color:
        var(--muted);

    font-size:
        13px;

    line-height:
        1.7;

}


/* =====================================================
   SECTION
===================================================== */

.section-title {

    margin:
        30px 0 15px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

}


.section-title h2 {

    font-size:
        20px;

}


.section-title span {

    color:
        var(--muted);

    font-size:
        13px;

}


/* =====================================================
   STATS
===================================================== */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        18px;

}


.stat {

    padding:
        21px;

    display:
        flex;

    align-items:
        center;

    gap:
        15px;

}


.stat-icon {

    width:
        52px;

    height:
        52px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        19px;

    background:

        linear-gradient(
            135deg,
            rgba(34,211,238,.18),
            rgba(139,92,246,.13)
        );

    border:
        1px solid
        rgba(255,255,255,.13);

    font-size:
        21px;

}


.stat h3 {

    font-size:
        25px;

    margin-bottom:
        3px;

}


.stat p {

    color:
        var(--muted);

    font-size:
        12px;

}


/* =====================================================
   CHILDREN
===================================================== */

.children-grid {

    display:
        grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:
        18px;

}


.child-card {

    padding:
        23px;

}


.child-header {

    display:
        flex;

    align-items:
        center;

    gap:
        14px;

    margin-bottom:
        20px;

}


.child-avatar {

    width:
        56px;

    height:
        56px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        20px;

    background:

        linear-gradient(
            135deg,
            #06b6d4,
            #6366f1
        );

    font-weight:
        700;

    box-shadow:

        0 12px 28px
        rgba(6,182,212,.20);

}


.child-info h3 {

    font-size:
        16px;

    margin-bottom:
        4px;

}


.child-info p {

    color:
        var(--muted);

    font-size:
        12px;

}


.child-details {

    padding-top:
        15px;

    border-top:
        1px solid
        rgba(255,255,255,.10);

}


.detail {

    display:
        flex;

    justify-content:
        space-between;

    padding:
        6px 0;

    font-size:
        13px;

}


.detail span:first-child {

    color:
        var(--muted);

}


/* =====================================================
   SERVICES
===================================================== */

.services-grid {

    display:
        grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:
        18px;

}


.service {

    display:
        block;

    padding:
        23px;

    color:
        #fff;

    text-decoration:
        none;

}


.service-icon {

    width:
        52px;

    height:
        52px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    margin-bottom:
        16px;

    border-radius:
        19px;

    background:

        linear-gradient(
            135deg,
            rgba(34,211,238,.16),
            rgba(139,92,246,.16)
        );

    border:
        1px solid
        rgba(255,255,255,.13);

    font-size:
        21px;

}


.service h3 {

    font-size:
        15px;

    margin-bottom:
        7px;

}


.service p {

    color:
        var(--muted);

    font-size:
        13px;

    line-height:
        1.6;

}


/* =====================================================
   EMPTY CHILDREN
===================================================== */

.empty {

    padding:
        55px 25px;

    text-align:
        center;

}


.empty-icon {

    width:
        70px;

    height:
        70px;

    margin:
        0 auto 17px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        25px;

    background:

        linear-gradient(
            135deg,
            rgba(34,211,238,.12),
            rgba(139,92,246,.12)
        );

    border:
        1px solid
        rgba(255,255,255,.12);

    font-size:
        28px;

}


.empty h3 {

    margin-bottom:
        8px;

}


.empty p {

    color:
        var(--muted);

    font-size:
        13px;

}


/* =====================================================
   LOGOUT
===================================================== */

.logout {

    margin-top:
        25px;

}


.logout a {

    display:
        block;

    padding:
        12px;

    text-align:
        center;

    text-decoration:
        none;

    color:
        #fda4af;

    border-radius:
        16px;

    background:
        rgba(244,63,94,.08);

    border:
        1px solid
        rgba(244,63,94,.14);

    transition:
        .3s;

}


.logout a:hover {

    color:
        #fff;

    background:
        rgba(244,63,94,.18);

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1200px) {

    .profile-grid {

        grid-template-columns:
            1fr 1fr;

    }

    .profile-grid
    .profile-card:first-child {

        grid-column:
            span 2;

    }

    .stats {

        grid-template-columns:
            repeat(2,1fr);

    }

    .children-grid,
    .services-grid {

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media (max-width: 850px) {

    .sidebar {

        position:
            relative;

        top:
            0;

        left:
            0;

        bottom:
            auto;

        width:
            calc(100% - 30px);

        height:
            auto;

        margin:
            15px;

    }


    .main {

        margin-left:
            0;

        padding:
            20px 15px 40px;

    }


    .topbar {

        flex-direction:
            column;

        align-items:
            flex-start;

        gap:
            15px;

    }


    .welcome {

        flex-direction:
            column;

        align-items:
            flex-start;

        gap:
            25px;

    }


    .profile-grid,
    .stats,
    .children-grid,
    .services-grid {

        grid-template-columns:
            1fr;

    }


    .profile-grid
    .profile-card:first-child {

        grid-column:
            auto;

    }

}


@media (max-width: 500px) {

    .main {

        padding:
            15px 10px 35px;

    }


    .welcome {

        padding:
            24px;

    }


    .welcome h2 {

        font-size:
            25px;

    }


    .user-box {

        width:
            100%;

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

