<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$errors = [];
$success = "";

$full_name = "";
$email = "";
$phone = "";
$address = "";
$occupation = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $occupation = trim($_POST["occupation"] ?? "");

    if ($full_name === "") {
        $errors[] = "Full name is required.";
    }

    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($errors)) {

        try {

            // Check duplicate email
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetch()) {

                $errors[] = "This email is already registered.";

            } else {

                // Generate default password from last name
                $name_parts = preg_split('/\s+/', trim($full_name));
                $last_name = end($name_parts);
                $default_password = strtolower($last_name);

                $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

                $pdo->beginTransaction();

                // Insert user
                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password, role, status)
                     VALUES (?, ?, ?, 'parent', 'active')"
                );
                $stmt->execute([$full_name, $email, $hashed_password]);

                $user_id = $pdo->lastInsertId();

                // Insert parent profile
                $stmt = $pdo->prepare(
                    "INSERT INTO parents (user_id, phone, address, occupation)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([
                    $user_id,
                    $phone ?: null,
                    $address ?: null,
                    $occupation ?: null
                ]);

                $pdo->commit();

                $success = "Parent added successfully. Default password: " . $default_password;

                $full_name = "";
                $email = "";
                $phone = "";
                $address = "";
                $occupation = "";
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parent | School Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="page-header">
        <div>
            <h1>Add New Parent</h1>
            <p>Create a new parent/guardian account.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">← Back</a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <div class="success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST">

            <h2>Parent Information</h2>

            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?= htmlspecialchars($full_name) ?>"
                       placeholder="Enter parent's full name" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($email) ?>"
                           placeholder="parent@example.com" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone"
                           value="<?= htmlspecialchars($phone) ?>"
                           placeholder="07XXXXXXXX">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address"
                       value="<?= htmlspecialchars($address) ?>"
                       placeholder="Enter address">
            </div>

            <div class="form-group">
                <label for="occupation">Occupation</label>
                <input type="text" id="occupation" name="occupation"
                       value="<?= htmlspecialchars($occupation) ?>"
                       placeholder="e.g. Businessman">
            </div>

            <p style="font-size:13px; color:#627d98;">
                Note: Default password will be the parent's last name (lowercase).
                The parent can change it later.
            </p>

            <div class="actions">
                <button type="submit">Add Parent</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>

</div>

</body>
</html>