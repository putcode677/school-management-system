
<?php

session_start();

require_once "../config/database.php";

// Authentication and authorization
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$full_name = $_SESSION["full_name"] ?? "Student";
$email = $_SESSION["email"] ?? "";

$page = $_GET["page"] ?? "dashboard";

$allowed_pages = [
    "dashboard",
    "subjects",
    "assignments",
    "results",
    "timetable",
    "attendance",
    "profile"
];

if (!in_array($page, $allowed_pages, true)) {
    $page = "dashboard";
}

// Escape HTML output
function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Fetch subjects and their assigned teachers
$subjects = [];
$subjects_error = false;

try {
    $subject_stmt = $pdo->prepare(
        "SELECT DISTINCT
            c.class_name,
            c.class_code,
            sub.subject_name,
            sub.subject_code,
            teacher_user.full_name AS teacher_name,
            t.phone AS teacher_phone
         FROM students s
         INNER JOIN student_classes sc
            ON sc.student_id = s.id
         INNER JOIN classes c
            ON c.id = sc.class_id
         INNER JOIN teacher_classes tc
            ON tc.class_id = c.id
         INNER JOIN teachers t
            ON t.id = tc.teacher_id
         INNER JOIN users teacher_user
            ON teacher_user.id = t.user_id
         INNER JOIN teacher_subjects ts
            ON ts.teacher_id = t.id
         INNER JOIN subjects sub
            ON sub.id = ts.subject_id
         WHERE s.user_id = ?
         ORDER BY c.class_name ASC, sub.subject_name ASC"
    );

    $subject_stmt->execute([$user_id]);

    $subjects = $subject_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log(
        "Student subjects query failed: " . $e->getMessage()
    );

    $subjects_error = true;
}

$subject_count = count($subjects);

$initial = function_exists("mb_substr")
    ? mb_substr($full_name, 0, 1, "UTF-8")
    : substr($full_name, 0, 1);

$initial = function_exists("mb_strtoupper")
    ? mb_strtoupper($initial, "UTF-8")
    : strtoupper($initial);

$page_titles = [
    "dashboard" => "Student Dashboard",
    "subjects" => "My Subjects",
    "assignments" => "Assignments",
    "results" => "Results",
    "timetable" => "Timetable",
    "attendance" => "Attendance",
    "profile" => "My Profile"
];

$current_title = $page_titles[$page];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= e($current_title) ?> | School System</title>

    <style>
        :root {
            --bg: #dbeafe;
            --surface: #dbeafe;
            --blue: #2563eb;
            --blue-dark: #1e40af;
            --text: #1e3a5f;
            --muted: #526783;
            --shadow-dark: #b8c9e2;
            --shadow-light: #ffffff;
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            -webkit-tap-highlight-color: transparent;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 20px;
            top: 20px;
            bottom: 20px;
            width: 240px;
            padding: 25px 18px;
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 25px;
            box-shadow:
                9px 9px 18px var(--shadow-dark),
                -9px -9px 18px var(--shadow-light);
            overflow-y: auto;
            z-index: 10;
        }

        .logo {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 35px;
            color: var(--blue-dark);
            letter-spacing: 0.3px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .menu a {
            display: block;
            padding: 14px 16px;
            border-radius: 15px;
            text-decoration: none;
            color: var(--muted);
            font-size: 14px;
            transition: 0.2s ease;
        }

        .menu a:hover,
        .menu a.active {
            color: var(--blue);
            background: rgba(255, 255, 255, 0.2);
            box-shadow:
                inset 4px 4px 8px var(--shadow-dark),
                inset -4px -4px 8px var(--shadow-light);
        }

        .menu a.active {
            font-weight: 700;
        }

        /* LOGOUT */

        .logout {
            margin-top: 28px;
            text-align: center;
        }

        .logout a {
            display: block;
            padding: 13px;
            border-radius: 15px;
            text-decoration: none;
            color: var(--danger);
            font-weight: 700;
            box-shadow:
                5px 5px 10px var(--shadow-dark),
                -5px -5px 10px var(--shadow-light);
            transition: 0.2s ease;
        }

        .logout a:active {
            box-shadow:
                inset 4px 4px 8px var(--shadow-dark),
                inset -4px -4px 8px var(--shadow-light);
        }

        /* MAIN CONTENT */

        .main {
            margin-left: 280px;
            padding: 30px;
            min-width: 0;
        }

        /* TOP BAR */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .page-title h1 {
            margin: 0;
            font-size: 28px;
            color: var(--blue-dark);
        }

        .page-title p {
            margin-top: 7px;
            color: var(--muted);
            font-size: 14px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .profile-info {
            overflow-wrap: anywhere;
        }

        .profile-info strong {
            color: var(--text);
        }

        .profile-info small {
            display: inline-block;
            margin-top: 4px;
            color: var(--muted);
        }

        .avatar {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            color: var(--blue);
            background: var(--surface);
            box-shadow:
                6px 6px 12px var(--shadow-dark),
                -6px -6px 12px var(--shadow-light);
        }

        /* WELCOME */

        .welcome {
            padding: 28px;
            border-radius: 25px;
            margin-bottom: 30px;
            background: var(--surface);
            box-shadow:
                9px 9px 18px var(--shadow-dark),
                -9px -9px 18px var(--shadow-light);
        }

        .welcome h2 {
            margin: 0 0 12px;
            font-size: 24px;
            color: var(--blue-dark);
        }

        .welcome p {
            margin: 8px 0;
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        /* STATISTICS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            padding: 22px 14px;
            border-radius: 22px;
            text-align: center;
            background: var(--surface);
            box-shadow:
                8px 8px 16px var(--shadow-dark),
                -8px -8px 16px var(--shadow-light);
        }

        .stat-icon {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .stat-card h3 {
            margin: 5px 0;
            font-size: 25px;
            color: var(--blue-dark);
        }

        .stat-card p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* DASHBOARD CARDS */

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .card {
            min-width: 0;
            padding: 25px;
            border-radius: 24px;
            text-align: center;
            background: var(--surface);
            box-shadow:
                8px 8px 16px var(--shadow-dark),
                -8px -8px 16px var(--shadow-light);
            transition: transform 0.25s ease;
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
                inset 5px 5px 10px var(--shadow-dark),
                inset -5px -5px 10px var(--shadow-light);
        }

        .card h3 {
            margin: 10px 0;
            font-size: 17px;
            color: var(--blue-dark);
        }

        .card p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
            min-height: 40px;
        }

        .card a {
            display: inline-block;
            margin-top: 12px;
            padding: 11px 18px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--blue);
            font-size: 13px;
            font-weight: 700;
            box-shadow:
                5px 5px 10px var(--shadow-dark),
                -5px -5px 10px var(--shadow-light);
            transition: 0.2s ease;
        }

        .card a:active {
            box-shadow:
                inset 4px 4px 8px var(--shadow-dark),
                inset -4px -4px 8px var(--shadow-light);
        }

        /* SUBJECTS */

        .section-heading {
            margin: 0 0 22px;
            color: var(--blue-dark);
            font-size: 24px;
        }

        .subject-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .subject-card {
            min-width: 0;
            padding: 24px;
            border-radius: 22px;
            background: var(--surface);
            box-shadow:
                8px 8px 16px var(--shadow-dark),
                -8px -8px 16px var(--shadow-light);
            transition: transform 0.25s ease;
        }

        .subject-card:hover {
            transform: translateY(-4px);
        }

        .subject-icon {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 17px;
            font-size: 27px;
            margin-bottom: 16px;
            box-shadow:
                inset 5px 5px 10px var(--shadow-dark),
                inset -5px -5px 10px var(--shadow-light);
        }

        .subject-card h3 {
            margin: 0 0 8px;
            color: var(--blue-dark);
            font-size: 19px;
            overflow-wrap: anywhere;
        }

        .subject-code {
            display: inline-block;
            margin: 0 0 16px;
            padding: 7px 12px;
            border-radius: 10px;
            color: var(--blue);
            font-size: 13px;
            font-weight: 800;
            box-shadow:
                inset 3px 3px 6px var(--shadow-dark),
                inset -3px -3px 6px var(--shadow-light);
        }

        .subject-detail {
            margin: 12px 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .subject-detail strong {
            display: block;
            margin-bottom: 3px;
            color: var(--text);
        }

        .teacher-phone {
            color: var(--blue);
            font-weight: 700;
            text-decoration: none;
            overflow-wrap: anywhere;
        }

        .teacher-phone:hover {
            color: var(--blue-dark);
            text-decoration: underline;
        }

        /* EMPTY AND ERROR STATES */

        .empty-state {
            padding: 30px;
            border-radius: 22px;
            text-align: center;
            background: var(--surface);
            box-shadow:
                inset 5px 5px 10px var(--shadow-dark),
                inset -5px -5px 10px var(--shadow-light);
        }

        .empty-icon {
            font-size: 38px;
            margin-bottom: 12px;
        }

        .empty-state h3,
        .empty-state h2 {
            color: var(--blue-dark);
        }

        .empty-state p {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.7;
        }

        /* ACCESSIBILITY */

        a:focus-visible {
            outline: 3px solid var(--blue);
            outline-offset: 4px;
        }

        /* RESPONSIVE */

        @media (max-width: 1200px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cards,
            .subject-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 750px) {
            .sidebar {
                position: relative;
                inset: auto;
                width: auto;
                margin: 12px;
                padding: 20px 14px;
            }

            .logo {
                margin-bottom: 22px;
            }

            .menu {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }

            .menu a {
                padding: 11px 12px;
            }

            .logout {
                margin-top: 20px;
            }

            .main {
                margin-left: 0;
                padding: 20px 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .page-title h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 500px) {
            .stats,
            .cards,
            .subject-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .welcome {
                padding: 22px;
            }

            .welcome h2 {
                font-size: 21px;
            }

            .profile {
                max-width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">
        🎓 SCHOOL SYSTEM
    </div>

    <nav class="menu">

        <a href="student.php?page=dashboard"
           class="<?= $page === 'dashboard' ? 'active' : '' ?>">
            🏠 Dashboard
        </a>

        <a href="student.php?page=subjects"
           class="<?= $page === 'subjects' ? 'active' : '' ?>">
            📚 My Subjects
        </a>

        <a href="student.php?page=assignments"
           class="<?= $page === 'assignments' ? 'active' : '' ?>">
            📝 Assignments
        </a>

        <a href="student.php?page=results"
           class="<?= $page === 'results' ? 'active' : '' ?>">
            📊 Results
        </a>

        <a href="student.php?page=timetable"
           class="<?= $page === 'timetable' ? 'active' : '' ?>">
            📅 Timetable
        </a>

        <a href="student.php?page=attendance"
           class="<?= $page === 'attendance' ? 'active' : '' ?>">
            📋 Attendance
        </a>

        <a href="student.php?page=profile"
           class="<?= $page === 'profile' ? 'active' : '' ?>">
            👤 My Profile
        </a>

    </nav>

    <div class="logout">
        <a href="../auth/logout.php">
            🚪 Logout
        </a>
    </div>

</aside>

<!-- MAIN CONTENT -->

<main class="main">

    <!-- TOP BAR -->

    <header class="topbar">

        <div class="page-title">
            <h1><?= e($current_title) ?></h1>
            <p>Manage your academic activities</p>
        </div>

        <div class="profile">

            <div class="avatar">
                <?= e($initial) ?>
            </div>

            <div class="profile-info">
                <strong><?= e($full_name) ?></strong>
                <br>
                <small>Student</small>
            </div>

        </div>

    </header>

    <?php if ($page === "dashboard"): ?>

        <!-- WELCOME -->

        <section class="welcome">

            <h2>
                Welcome back, <?= e($full_name) ?> 👋
            </h2>

            <?php if ($email !== ""): ?>
                <p>Email: <?= e($email) ?></p>
            <?php endif; ?>

            <p>
                Here's an overview of your academic activities.
            </p>

        </section>

        <!-- STATISTICS -->

        <section class="stats">

            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <h3><?= $subject_count ?></h3>
                <p>My Subjects</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <h3>—</h3>
                <p>Assignments</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <h3>—</h3>
                <p>Results</p>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <h3>—</h3>
                <p>Attendance</p>
            </div>

        </section>

        <!-- DASHBOARD CARDS -->

        <section class="cards">

            <div class="card">
                <div class="card-icon">📚</div>
                <h3>My Subjects</h3>
                <p>View subjects, subject codes, teachers and their phone numbers.</p>
                <a href="student.php?page=subjects">
                    View Subjects
                </a>
            </div>

            <div class="card">
                <div class="card-icon">📝</div>
                <h3>Assignments</h3>
                <p>View your assignments and deadlines.</p>
                <a href="student.php?page=assignments">
                    View Assignments
                </a>
            </div>

            <div class="card">
                <div class="card-icon">📊</div>
                <h3>Results</h3>
                <p>View your examination results.</p>
                <a href="student.php?page=results">
                    View Results
                </a>
            </div>

            <div class="card">
                <div class="card-icon">📅</div>
                <h3>Timetable</h3>
                <p>View your class timetable.</p>
                <a href="student.php?page=timetable">
                    View Timetable
                </a>
            </div>

            <div class="card">
                <div class="card-icon">📋</div>
                <h3>Attendance</h3>
                <p>Check your attendance records.</p>
                <a href="student.php?page=attendance">
                    View Attendance
                </a>
            </div>

            <div class="card">
                <div class="card-icon">👤</div>
                <h3>My Profile</h3>
                <p>View your profile information.</p>
                <a href="student.php?page=profile">
                    My Profile
                </a>
            </div>

        </section>

    <?php elseif ($page === "subjects"): ?>

        <!-- MY SUBJECTS -->

        <section class="subjects-page">

            <h2 class="section-heading">My Subjects</h2>

            <?php if ($subjects_error): ?>

                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3>Unable to Load Subjects</h3>
                    <p>
                        Please try again later. If the problem continues,
                        contact the school administrator.
                    </p>
                </div>

            <?php elseif (empty($subjects)): ?>

                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <h3>No Subjects Assigned Yet</h3>
                    <p>
                        Your subjects and teachers will appear here
                        after your class and teacher assignments
                        have been configured by the administrator.
                    </p>
                </div>

            <?php else: ?>

                <div class="subject-grid">

                    <?php foreach ($subjects as $subject): ?>

                        <article class="subject-card">

                            <div class="subject-icon">📖</div>

                            <h3>
                                <?= e($subject["subject_name"]) ?>
                            </h3>

                            <p class="subject-code">
                                <?= e($subject["subject_code"]) ?>
                            </p>

                            <p class="subject-detail">
                                <strong>Teacher Name</strong>
                                <?= e($subject["teacher_name"]) ?>
                            </p>

                            <p class="subject-detail">
                                <strong>Teacher Phone</strong>

                                <?php if (
                                    !empty($subject["teacher_phone"])
                                ): ?>

                                    <a
                                        class="teacher-phone"
                                        href="tel:<?= e($subject["teacher_phone"]) ?>"
                                    ><?= e($subject["teacher_phone"]) ?></a>

                                <?php else: ?>

                                    <span>Not provided</span>

                                <?php endif; ?>

                            </p>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    <?php elseif ($page === "profile"): ?>

        <section class="subjects-page">

            <h2 class="section-heading">My Profile</h2>

            <article class="subject-card">

                <div class="subject-icon">👤</div>

                <h3><?= e($full_name) ?></h3>

                <p class="subject-detail">
                    <strong>Role</strong>
                    Student
                </p>

                <p class="subject-detail">
                    <strong>Email</strong>
                    <?= $email !== "" ? e($email) : "Not provided" ?>
                </p>

            </article>

        </section>

    <?php else: ?>

        <section class="empty-state">

            <div class="empty-icon">🚧</div>

            <h2><?= e($current_title) ?></h2>

            <p>
                This section has not been implemented yet.
            </p>

            <p>
                You can visit My Subjects to view the subjects
                and teachers assigned to your class.
            </p>

            <p>
                <a href="student.php?page=subjects"
                   class="teacher-phone">
                    Go to My Subjects
                </a>
            </p>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
