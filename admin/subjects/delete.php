<?php

session_start();

require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get subject ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

try {

    // Check if subject exists
    $check = $pdo->prepare(
        "SELECT id
         FROM subjects
         WHERE id = ?"
    );

    $check->execute([$id]);

    $subject = $check->fetch(PDO::FETCH_ASSOC);

    if (!$subject) {
        header("Location: index.php");
        exit();
    }

    // Delete subject
    $delete = $pdo->prepare(
        "DELETE FROM subjects
         WHERE id = ?"
    );

    $delete->execute([$id]);

    // Return to subjects list
    header("Location: index.php");
    exit();

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}
?>

