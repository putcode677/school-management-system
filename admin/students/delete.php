<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get student ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

try {

    // Get user_id belonging to this student
    $stmt = $pdo->prepare(
        "SELECT user_id
         FROM students
         WHERE id = ?"
    );

    $stmt->execute([$id]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: index.php");
        exit();
    }

    $user_id = $student['user_id'];

    // Start transaction
    $pdo->beginTransaction();

    // Delete student profile
    $deleteStudent = $pdo->prepare(
        "DELETE FROM students WHERE id = ?"
    );

    $deleteStudent->execute([$id]);

    // Delete user account
    $deleteUser = $pdo->prepare(
        "DELETE FROM users WHERE id = ?"
    );

    $deleteUser->execute([$user_id]);

    // Commit
    $pdo->commit();

    // Return to students list
    header("Location: index.php");
    exit();

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Database Error: " . $e->getMessage());
}
?>

