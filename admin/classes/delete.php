<?php
session_start();

require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get class ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

try {

    // Check if class exists
    $check = $pdo->prepare(
        "SELECT id
         FROM classes
         WHERE id = ?"
    );

    $check->execute([$id]);

    $class = $check->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        header("Location: index.php");
        exit();
    }

    // Delete class
    $delete = $pdo->prepare(
        "DELETE FROM classes
         WHERE id = ?"
    );

    $delete->execute([$id]);

    // Return to classes list
    header("Location: index.php");
    exit();

} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}
?>

