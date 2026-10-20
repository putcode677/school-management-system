<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

try {
    // Find parent and associated user_id
    $stmt = $pdo->prepare("SELECT id, user_id FROM parents WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        header("Location: index.php");
        exit();
    }

    $pdo->beginTransaction();

    // Delete parent profile
    $delParent = $pdo->prepare("DELETE FROM parents WHERE id = ?");
    $delParent->execute([$id]);

    // Delete associated user record
    $delUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $delUser->execute([$parent['user_id']]);

    $pdo->commit();

    header("Location: index.php?deleted=1");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}
?>

