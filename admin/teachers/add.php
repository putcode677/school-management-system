<?php
session_start();

require_once "../../config/database.php";

// Only admin can access this page
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../../auth/login.php");
    exit;
}

$errors = [];
$success = "";

$full_name = "";
$email = "";
$employee_number = "";
$phone = "";
$address = "";
$specialization = "";
$hire_date = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $employee_number = trim($_POST["employee_number"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $specialization = trim($_POST["specialization"] ?? "");
    $hire_date = $_POST["hire_date"] ?? "";

    // Validation
    if ($full_name === "") {
        $errors[] = "Full name is required.";
    }

    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($password === "") {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($employee_number === "") {
        $errors[] = "Employee number is required.";
    }

    // Continue only if there are no validation errors
    if (empty($errors)) {

        try {

            // Check if email already exists
            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errors[] = "This email is already registered.";
            }

            // Check if employee number already exists
            $stmt = $pdo->prepare(
                "SELECT id FROM teachers WHERE employee_number = ? LIMIT 1"
            );
            $stmt->execute([$employee_number]);

            if ($stmt->fetch()) {
                $errors[] = "This employee number already exists.";
            }

            // Insert teacher
            if (empty($errors)) {

                $pdo->beginTransaction();

                // Create user account
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, password, role, status)
                    VALUES (?, ?, ?, 'teacher', 'active')"
                );

                $stmt->execute([
                    $full_name,
                    $email,
                    $hashed_password
                ]);

                $user_id = $pdo->lastInsertId();

                // Create teacher record
                $stmt = $pdo->prepare(
                    "INSERT INTO teachers
                    (user_id, employee_number, phone, address, specialization, hire_date)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $user_id,
                    $employee_number,
                    $phone ?: null,
                    $address ?: null,
                    $specialization ?: null,
                    $hire_date ?: null
                ]);

                $pdo->commit();

                $success = "Teacher added successfully.";

                // Clear form
                $full_name = "";
                $email = "";
                $employee_number = "";
                $phone = "";
                $address = "";
                $specialization = "";
                $hire_date = "";
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

    <title>Add Teacher | School Management System</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #dbeafe;
            color: #243b53;
            padding: 35px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        }

        .title-area h1 {
            font-size: 30px;
            color: #174a7e;
            margin-bottom: 7px;
        }

        .title-area p {
            color: #52708f;
        }

        .back-btn {
            text-decoration: none;
            color: #1769aa;
            padding: 14px 22px;
            border-radius: 15px;
            background: #dbeafe;
            box-shadow:
                8px 8px 16px #b8c9dc,
                -8px -8px 16px #ffffff;
            font-weight: 600;
        }

        .back-btn:hover {
            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        .form-card {
            background: #dbeafe;
            padding: 35px;
            border-radius: 25px;
            box-shadow:
                15px 15px 30px #b8c9dc,
                -15px -15px 30px #ffffff;
        }

        .section-title {
            font-size: 20px;
            color: #1769aa;
            margin-bottom: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            margin-bottom: 9px;
            font-weight: 600;
            color: #355d7a;
        }

        input,
        textarea {
            width: 100%;
            border: none;
            outline: none;
            background: #dbeafe;
            color: #243b53;
            padding: 15px 17px;
            border-radius: 14px;
            font-size: 15px;

            box-shadow:
                inset 6px 6px 12px #b8c9dc,
                inset -6px -6px 12px #ffffff;
        }

        input:focus,
        textarea:focus {
            box-shadow:
                inset 8px 8px 15px #b0c3d8,
                inset -8px -8px 15px #ffffff;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .password-note {
            margin-top: 7px;
            font-size: 12px;
            color: #66829a;
        }

        .messages {
            margin-bottom: 25px;
        }

        .error-box {
            background: #fce8e8;
            color: #b42318;
            padding: 15px 18px;
            border-radius: 14px;
            margin-bottom: 10px;
            box-shadow:
                6px 6px 12px #c9bcbc,
                -6px -6px 12px #ffffff;
        }

        .success-box {
            background: #e2f7eb;
            color: #167447;
            padding: 16px 18px;
            border-radius: 14px;
            margin-bottom: 10px;
            box-shadow:
                6px 6px 12px #b8c9dc,
                -6px -6px 12px #ffffff;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            border: none;
            cursor: pointer;
            text-decoration: none;
            padding: 15px 28px;
            border-radius: 15px;
            font-size: 15px;
            font-weight: 700;
            background: #dbeafe;
            color: #1769aa;

            box-shadow:
                8px 8px 16px #b8c9dc,
                -8px -8px 16px #ffffff;
        }

        .btn:hover {
            box-shadow:
                inset 6px 6px 12px #b8c9dc,
                inset -6px -6px 12px #ffffff;
        }

        .btn-primary {
            color: #ffffff;
            background: #2f80c0;
            box-shadow:
                8px 8px 16px #9db7ce,
                -8px -8px 16px #ffffff;
        }

        .btn-primary:hover {
            background: #2875b2;
            box-shadow:
                inset 5px 5px 10px #245f8f,
                inset -5px -5px 10px #4e9bd0;
        }

        .required {
            color: #d64545;
        }

        @media (max-width: 700px) {

            body {
                padding: 20px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-card {
                padding: 22px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

    </style>
</head>

<body>

<div class="container">

    <div class="header">

        <div class="title-area">
            <h1>👨‍🏫 Add Teacher</h1>
            <p>Create a new teacher account and profile.</p>
        </div>

        <a href="index.php" class="back-btn">
            ← Back to Teachers
        </a>

    </div>

    <div class="messages">

        <?php if (!empty($errors)): ?>

            <?php foreach ($errors as $error): ?>

                <div class="error-box">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>

        <?php if ($success): ?>

            <div class="success-box">
                ✅ <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>

    </div>

    <div class="form-card">

        <div class="section-title">
            Teacher Information
        </div>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group full">

                    <label>
                        Full Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        value="<?= htmlspecialchars($full_name) ?>"
                        placeholder="Enter teacher full name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email <span class="required">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        placeholder="teacher@example.com"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Password <span class="required">*</span>
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter password"
                        minlength="6"
                        required
                    >

                    <span class="password-note">
                        Minimum 6 characters.
                    </span>

                </div>


                <div class="form-group">

                    <label>
                        Employee Number <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="employee_number"
                        value="<?= htmlspecialchars($employee_number) ?>"
                        placeholder="e.g. EMP001"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="<?= htmlspecialchars($phone) ?>"
                        placeholder="e.g. 0712345678"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Specialization
                    </label>

                    <input
                        type="text"
                        name="specialization"
                        value="<?= htmlspecialchars($specialization) ?>"
                        placeholder="e.g. Mathematics"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Hire Date
                    </label>

                    <input
                        type="date"
                        name="hire_date"
                        value="<?= htmlspecialchars($hire_date) ?>"
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Address
                    </label>

                    <textarea
                        name="address"
                        placeholder="Enter teacher address"
                    ><?= htmlspecialchars($address) ?></textarea>

                </div>

            </div>


            <div class="actions">

                <a href="index.php" class="btn">
                    Cancel
                </a>

                <button type="submit" class="btn btn-primary">
                    ➕ Add Teacher
                </button>

            </div>

        </form>

    </div>

</div>

</body>
</html>
