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

$errors = [];
$success = "";

// Load existing parent
try {
    $stmt = $pdo->prepare("
        SELECT
            parents.id,
            parents.user_id,
            parents.phone,
            parents.address,
            parents.occupation,
            parents.parent_number,
            users.full_name,
            users.email,
            users.status
        FROM parents
        INNER JOIN users ON parents.user_id = users.id
        WHERE parents.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$full_name = $parent['full_name'];
$email = $parent['email'];
$phone = $parent['phone'] ?? '';
$address = $parent['address'] ?? '';
$occupation = $parent['occupation'] ?? '';
$parent_number = $parent['parent_number'] ?? '';
$status = $parent['status'] ?? 'active';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $occupation = trim($_POST["occupation"] ?? "");
    $parent_number = trim($_POST["parent_number"] ?? "");
    $status = $_POST["status"] ?? "active";

    if ($full_name === "") {
        $errors[] = "Full name is required.";
    }

    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($parent_number === "") {
        $errors[] = "Parent Number is required.";
    }

    if (empty($errors)) {

        try {
            // Check duplicate email
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
            $checkEmail->execute([$email, $parent['user_id']]);

            if ($checkEmail->fetch()) {
                $errors[] = "This email is already registered to another user.";
            }

            // Check duplicate parent_number
            $checkNum = $pdo->prepare("SELECT id FROM parents WHERE UPPER(parent_number) = UPPER(?) AND id != ?");
            $checkNum->execute([$parent_number, $id]);

            if ($checkNum->fetch()) {
                $errors[] = "This Parent Number is already in use.";
            }

            if (empty($errors)) {

                $pdo->beginTransaction();

                // Update users table
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare("
                        UPDATE users
                        SET full_name = ?, email = ?, password = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmtUser->execute([$full_name, $email, $hashed_password, $status, $parent['user_id']]);
                } else {
                    $stmtUser = $pdo->prepare("
                        UPDATE users
                        SET full_name = ?, email = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmtUser->execute([$full_name, $email, $status, $parent['user_id']]);
                }

                // Update parents table
                $stmtParent = $pdo->prepare("
                    UPDATE parents
                    SET phone = ?, address = ?, occupation = ?, parent_number = ?
                    WHERE id = ?
                ");
                $stmtParent->execute([
                    $phone ?: null,
                    $address ?: null,
                    $occupation ?: null,
                    $parent_number,
                    $id
                ]);

                $pdo->commit();

                $success = "Parent information updated successfully.";
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
    <title>Edit Parent | School Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="page-header">
        <div>
            <h1>Edit Parent</h1>
            <p>Update parent/guardian account and profile.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="../../dashboard/admin.php" class="btn btn-secondary">← Dashboard</a>
            <a href="index.php" class="btn btn-secondary">← Back to Parents</a>
        </div>
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

            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?= htmlspecialchars($full_name) ?>" required>
                </div>

                <div class="form-group">
                    <label for="parent_number">Parent Number (Login ID) *</label>
                    <input type="text" id="parent_number" name="parent_number"
                           value="<?= htmlspecialchars($parent_number) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone"
                           value="<?= htmlspecialchars($phone) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address"
                       value="<?= htmlspecialchars($address) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="occupation">Occupation</label>
                    <input type="text" id="occupation" name="occupation"
                           value="<?= htmlspecialchars($occupation) ?>">
                </div>

                <div class="form-group">
                    <label for="status">Account Status</label>
                    <select id="status" name="status">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <h2>Security</h2>

            <div class="form-group">
                <label for="password">New Password (leave blank to keep current)</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter new password if changing">
            </div>

            <div class="actions">
                <button type="submit">Update Parent</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>

</div>

</body>
</html>

