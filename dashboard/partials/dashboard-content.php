<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Forbidden");
}
require_once "../../config/database.php";

$total_students = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_teachers = (int) $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$total_subjects = (int) $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$total_classes  = (int) $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$total_parents  = (int) $pdo->query("SELECT COUNT(*) FROM parents")->fetchColumn();

$full_name = $_SESSION["full_name"] ?? "Administrator";
?>
<section class="welcome">
    <h2>Welcome back, <?= htmlspecialchars($full_name) ?> 👋</h2>
    <p>Here is an overview of your school management system.</p>
</section>

<div class="stats">
    <div class="stat-card"><div class="stat-icon">🎓</div><h3><?= $total_students ?></h3><p>Total Students</p></div>
    <div class="stat-card"><div class="stat-icon">👨‍🏫</div><h3><?= $total_teachers ?></h3><p>Total Teachers</p></div>
    <div class="stat-card"><div class="stat-icon">📚</div><h3><?= $total_subjects ?></h3><p>Total Subjects</p></div>
    <div class="stat-card"><div class="stat-icon">🏫</div><h3><?= $total_classes ?></h3><p>Total Classes</p></div>
    <div class="stat-card"><div class="stat-icon">👨‍👩‍👧</div><h3><?= $total_parents ?></h3><p>Total Parents</p></div>
</div>

<div class="section-title">
    <h2>School Management</h2>
    <p>Manage different sections of your school.</p>
</div>

<div class="cards">
    <a class="card nav-link" data-page="students"><div class="card-icon">🎓</div><h3>Students</h3><p>Add, edit, view and manage student information.</p></a>
    <a class="card nav-link" data-page="teachers"><div class="card-icon">👨‍🏫</div><h3>Teachers</h3><p>Manage teachers, employee information and specialization.</p></a>
    <a class="card nav-link" data-page="parents"><div class="card-icon">👨‍👩‍👧</div><h3>Parents</h3><p>Manage parent information and student relationships.</p></a>
    <a class="card nav-link" data-page="subjects"><div class="card-icon">📚</div><h3>Subjects</h3><p>Create and manage school subjects.</p></a>
    <a class="card nav-link" data-page="classes"><div class="card-icon">🏫</div><h3>Classes</h3><p>Manage classes and classroom information.</p></a>
    <a href="#" class="card"><div class="card-icon">📋</div><h3>Attendance</h3><p>Monitor and manage student attendance.</p></a>
    <a href="#" class="card"><div class="card-icon">📊</div><h3>Results</h3><p>Manage student academic results and performance.</p></a>
    <a href="#" class="card"><div class="card-icon">⚙️</div><h3>Settings</h3><p>Configure system settings and administration options.</p></a>
</div>
