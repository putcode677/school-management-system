<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$full_name = $_SESSION["full_name"] ?? "Administrator";
$email = $_SESSION["email"] ?? "";
$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/school-management-system/assets/css/admin-layout.css">
</head>
<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main" id="mainArea">

    <div class="topbar">
        <button class="hamburger" id="hamburgerBtn">☰</button>
        <div>
            <h1 id="pageTitle">Admin Dashboard</h1>
            <p id="pageSubtitle">Manage your school system from one place.</p>
        </div>
        <div class="admin-profile">
            <div class="avatar"><?= strtoupper(substr($full_name, 0, 1)) ?></div>
            <div class="profile-info">
                <strong><?= htmlspecialchars($full_name) ?></strong>
                <span><?= htmlspecialchars($email) ?></span>
            </div>
        </div>
    </div>

    <div id="main-content"></div>

</main>

<script>
const BASE = "/school-management-system";
const mainContent = document.getElementById("main-content");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("sidebarOverlay");

const pageMap = {
    dashboard: BASE + "/dashboard/partials/dashboard-content.php",
    students:  BASE + "/admin/students/index.php",
    teachers:  BASE + "/admin/teachers/index.php",
    parents:   BASE + "/admin/parents/index.php",
    subjects:  BASE + "/admin/subjects/index.php",
    classes:   BASE + "/admin/classes/index.php"
};

window.loadPageGlobal = function loadPage(page, pushState = true) {
    const url = pageMap[page];
    if (!url) return;

    fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then(res => res.text())
        .then(html => {
            mainContent.innerHTML = html;

            document.querySelectorAll(".nav-link").forEach(a => a.classList.remove("active"));
            document.querySelectorAll('.nav-link[data-page="' + page + '"]').forEach(a => a.classList.add("active"));

            if (pushState) {
                history.pushState({ page }, "", "#" + page);
            }

            sidebar.classList.remove("open");
            overlay.classList.remove("show");

            bindCardLinks();
        })
        .catch(() => {
            mainContent.innerHTML = "<p style='color:red;'>Failed to load page.</p>";
        });
};

function bindCardLinks() {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.onclick = (e) => {
            e.preventDefault();
            const page = link.getAttribute('data-page');
            window.loadPageGlobal(page);
        };
    });
}

window.addEventListener("popstate", (e) => {
    const page = (e.state && e.state.page) || "dashboard";
    window.loadPageGlobal(page, false);
});

document.getElementById("hamburgerBtn").addEventListener("click", () => {
    sidebar.classList.toggle("open");
    overlay.classList.toggle("show");
});

overlay.addEventListener("click", () => {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
});

const initialPage = window.location.hash ? window.location.hash.substring(1) : "dashboard";
window.loadPageGlobal(initialPage, false);
history.replaceState({ page: initialPage }, "", "#" + initialPage);
</script>

</body>
</html>
