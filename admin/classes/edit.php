<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}
s
// Get class ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

$message = "";
$message_type = "";

$class_name = "";
$class_code = "";
$description = "";

// Get class information
try {
    $stmt = $pdo->prepare(
        "SELECT id, class_name, class_code, description
         FROM classes
         WHERE id = ?"
    );

    $stmt->execute([$id]);

    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        header("Location: index.php");
        exit();
    }

    $class_name = $class['class_name'];
    $class_code = $class['class_code'];
    $description = $class['description'];

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Update class
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $class_name = trim($_POST['class_name'] ?? '');
    $class_code = trim($_POST['class_code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if ($class_name === '' || $class_code === '') {

        $message = "Class Name and Class Code are required.";
        $message_type = "error";

    } else {

        try {

            // Check duplicate class code
            $check = $pdo->prepare(
                "SELECT id
                 FROM classes
                 WHERE class_code = ?
                 AND id != ?"
            );

            $check->execute([$class_code, $id]);

            if ($check->fetch()) {

                $message = "This Class Code already exists.";
                $message_type = "error";

            } else {

                // Update class
                $update = $pdo->prepare(
                    "UPDATE classes
                     SET class_name = ?,
                         class_code = ?,
                         description = ?
                     WHERE id = ?"
                );

                $update->execute([
                    $class_name,
                    $class_code,
                    $description !== '' ? $description : null,
                    $id
                ]);

                // Redirect after successful update
                header("Location: index.php");
                exit();
            }

        } catch (PDOException $e) {

            $message = "Database Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Class</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #dbeafe;
            color: #243b53;
        }

        .container {
            width: 100%;
            max-width: 650px;
            margin: 50px auto;
            padding: 25px;
        }

        .card {
            background: #dbeafe;
            border-radius: 25px;
            padding: 35px;
            box-shadow:
                12px 12px 25px #b8c9dc,
                -12px -12px 25px #ffffff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .back-btn {
            text-decoration: none;
            color: #243b53;
            background: #dbeafe;
            padding: 12px 18px;
            border-radius: 14px;
            box-shadow:
                6px 6px 12px #b8c9dc,
                -6px -6px 12px #ffffff;
            font-weight: bold;
        }

        .back-btn:active {
            box-shadow:
                inset 4px 4px 8px #b8c9dc,
                inset -4px -4px 8px #ffffff;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-weight: bold;
        }

        input,
        textarea {
            width: 100%;
            border: none;
            outline: none;
            background: #dbeafe;
            color: #243b53;
            padding: 15px;
            border-radius: 15px;
            font-size: 15px;
            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            box-shadow:
                inset 3px 3px 7px #b8c9dc,
                inset -3px -3p

