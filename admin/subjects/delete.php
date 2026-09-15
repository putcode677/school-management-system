<?php

session_start();

require_once "../../config/database.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit;
}

// Only admin
if ($_SESSION["role"] !== "admin") {
    header("Location: ../../auth/login.php");
    exit;
}

// Get subject ID
$id = $_GET["id"] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: index.php");
    exit;
}

// Check if subject exists
$stmt = $pdo->prepare("
    SELECT id
    FROM subjects
    WHERE id = ?
");

$stmt->execute([$id]);

$subject = $stmt->fetch();

if (!$subject) {
    header("Location: index.php");
    exit;
}

// Delete subject
$delete = $pdo->prepare("
    DELETE FROM subjects
    WHERE id = ?
");

$delete->execute([$id]);

// Return to subjects page
header("Location: index.php");
exit;

?>


