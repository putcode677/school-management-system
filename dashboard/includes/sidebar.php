<?php
$active = $active ?? '';
function navActive($key, $active) {
    return $key === $active ? 'active' : '';
}
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="logo">
        <div class="logo-icon">🏫</div>
        <h2>School Admin</h2>
        <p>Management System</p>
    </div>

    <ul class="menu">
        <li><a data-page="dashboard" class="nav-link <?= navActive('dashboard', $active) ?>"><div class="menu-icon">🏠</div><span>Dashboard</span></a></li>
        <li><a data-page="students" class="nav-link <?= navActive('students', $active) ?>"><div class="menu-icon">🎓</div><span>Students</span></a></li>
        <li><a data-page="teachers" class="nav-link <?= navActive('teachers', $active) ?>"><div class="menu-icon">👨‍🏫</div><span>Teachers</span></a></li>
        <li><a data-page="parents" class="nav-link <?= navActive('parents', $active) ?>"><div class="menu-icon">👨‍👩‍👧</div><span>Parents</span></a></li>
        <li><a data-page="subjects" class="nav-link <?= navActive('subjects', $active) ?>"><div class="menu-icon">📚</div><span>Subjects</span></a></li>
        <li><a data-page="classes" class="nav-link <?= navActive('classes', $active) ?>"><div class="menu-icon">🏫</div><span>Classes</span></a></li>
        <li><a href="#"><div class="menu-icon">📋</div><span>Attendance</span></a></li>
        <li><a href="#"><div class="menu-icon">📊</div><span>Results</span></a></li>
        <li><a href="#"><div class="menu-icon">⚙️</div><span>Settings</span></a></li>
    </ul>

    <div class="logout">
        <a href="/school-management-system/auth/logout.php">
            <div>🚪</div><span>Logout</span>
        </a>
    </div>
</aside>
