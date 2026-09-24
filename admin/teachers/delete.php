<?php
session_start();

require_once "../../config/database.php";

// Only logged-in admin
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../auth/login.php");
    exit;
}

// Get teacher ID
$teacher_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($teacher_id <= 0) {
    header("Location: index.php");
    exit;
}

try {

    // Find teacher and related user
    $stmt = $pdo->prepare("
        SELECT
            teachers.id,
            teachers.user_id,
            users.full_name
        FROM teachers
        INNER JOIN users ON teachers.user_id = users.id
        WHERE teachers.id = ?
        LIMIT 1
    ");

    $stmt->execute([$teacher_id]);

    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        header("Location: index.php");
        exit;
    }

    // Delete teacher
    $pdo->beginTransaction();

    // Delete teacher record first
    $stmt = $pdo->prepare("
        DELETE FROM teachers
        WHERE id = ?
    ");

    $stmt->execute([$teacher_id]);

    // Delete associated user account
    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = ?
    ");

    $stmt->execute([$teacher["user_id"]]);

    $pdo->commit();

    header("Location: index.php?deleted=1");
    exit;

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(
        "Unable to delete teacher: " .
        htmlspecialchars($e->getMessage())
    );
}
?>

