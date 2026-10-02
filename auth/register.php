<?php

require_once "../config/database.php";

$message = "";
$message_type = "";

$full_name = "";
$email = "";
$role = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "";

    // Check empty fields
    if (
        empty($full_name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password) ||
        empty($role)
    ) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    }

    // Validate email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    }

    // Check password length
    elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
        $message_type = "error";
    }

    // Confirm password
    elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    }

    // Check allowed roles
    elseif (!in_array($role, ["teacher", "student", "parent"], true)) {
        $message = "Invalid role selected.";
        $message_type = "error";
    }

    else {

        try {

            // Check if email already exists
            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );

            $check->execute([$email]);

            if ($check->fetch()) {

                $message = "This email is already registered.";
                $message_type = "error";

            } else {

                // Hash password
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Start transaction
                $pdo->beginTransaction();

                // Create user account
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, password, role, status)
                    VALUES (?, ?, ?, ?, 'active')
                    RETURNING id"
                );

                $stmt->execute([
                    $full_name,
                    $email,
                    $hashed_password,
                    $role
                ]);

                $user_id = $stmt->fetchColumn();

                /*
                 * If the user registers as a parent,
                 * also create the parent profile.
                 */
                if ($role === "parent") {

                    $parent_stmt = $pdo->prepare(
                        "INSERT INTO parents (user_id)
                         VALUES (?)"
                    );

                    $parent_stmt->execute([$user_id]);
                }

                // Finish transaction
                $pdo->commit();

                $message = "Registration successful! You can now login.";
                $message_type = "success";

                // Clear form
                $full_name = "";
                $email = "";
                $role = "";
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = "Registration failed. Please try again.";
            $message_type = "error";

            // For development, you can temporarily see the actual error:
            // $message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Register - School Management System</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {

            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 25px;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #e8eef7,
                    #dce6f3,
                    #edf3fa
                );

            color: #263d63;
        }

        .register-container {

            width: 100%;
            max-width: 500px;

            padding: 45px;

            border-radius: 32px;

            background: #e8eef7;

            box-shadow:
                18px 18px 40px
                rgba(163, 177, 198, 0.55),

                -18px -18px 40px
                rgba(255, 255, 255, 0.95);
        }

        h2 {

            text-align: center;

            color: #203a62;

            font-size: 30px;

            margin-bottom: 10px;
        }

        .subtitle {

            text-align: center;

            color: #7188a9;

            margin-bottom: 28px;
        }

        label {

            display: block;

            margin-bottom: 8px;

            color: #5c7599;

            font-size: 14px;

            font-weight: 600;
        }

        .form-group {

            margin-bottom: 18px;
        }

        input,
        select {

            width: 100%;

            height: 54px;

            padding: 0 18px;

            border: none;

            outline: none;

            border-radius: 27px;

            background: #e4ebf5;

            color: #314d76;

            font-size: 15px;

            box-shadow:

                inset 5px 5px 10px
                rgba(163, 177, 198, 0.4),

                inset -5px -5px 10px
                rgba(255, 255, 255, 0.9);
        }

        input:focus,
        select:focus {

            box-shadow:

                inset 4px 4px 8px
                rgba(163, 177, 198, 0.4),

                inset -4px -4px 8px
                rgba(255, 255, 255, 0.9),

                0 0 0 3px
                rgba(45, 119, 239, 0.12);
        }

        .message {

            padding: 14px;

            margin-bottom: 22px;

            border-radius: 18px;

            text-align: center;

            font-size: 14px;
        }

        .success {

            background: #e2f5eb;

            color: #18764a;
        }

        .error {

            background: #f8e4e7;

            color: #c43d4c;
        }

        button {

            width: 100%;

            height: 56px;

            margin-top: 8px;

            border: none;

            border-radius: 28px;

            background:
                linear-gradient(
                    135deg,
                    #4f96ff,
                    #246be5
                );

            color: white;

            font-size: 16px;

            font-weight: 600;

            cursor: pointer;

            box-shadow:

                8px 8px 18px
                rgba(76, 117, 178, 0.45),

                -5px -5px 12px
                rgba(255, 255, 255, 0.75);
        }

        button:hover {

            transform: translateY(-2px);
        }

        .login-link {

            text-align: center;

            margin-top: 25px;

            color: #7188a9;

            font-size: 14px;
        }

        .login-link a {

            color: #216ce5;

            font-weight: 600;

            text-decoration: none;
        }

        .login-link a:hover {

            text-decoration: underline;
        }

        @media (max-width: 550px) {

            .register-container {

                padding: 30px 22px;

                border-radius: 25px;
            }

            h2 {

                font-size: 25px;
            }
        }

    </style>

</head>

<body>

<div class="register-container">

    <h2>Create Account</h2>

    <div class="subtitle">
        School Management System
    </div>

    <?php if (!empty($message)): ?>

        <div class="message <?php echo htmlspecialchars($message_type); ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label for="full_name">
                Full Name
            </label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                placeholder="Enter your full name"
                value="<?php echo htmlspecialchars($full_name); ?>"
                required
            >

        </div>

        <div class="form-group">

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                value="<?php echo htmlspecialchars($email); ?>"
                required
            >

        </div>

        <div class="form-group">

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter password"
                required
            >

        </div>

        <div class="form-group">

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm password"
                required
            >

        </div>

        <div class="form-group">

            <label for="role">
                Register As
            </label>

            <select
                id="role"
                name="role"
                required
            >

                <option value="">
                    -- Select Role --
                </option>

                <option
                    value="student"
                    <?php echo $role === "student" ? "selected" : ""; ?>
                >
                    Student
                </option>

                <option
                    value="teacher"
                    <?php echo $role === "teacher" ? "selected" : ""; ?>
                >
                    Teacher
                </option>

                <option
                    value="parent"
                    <?php echo $role === "parent" ? "selected" : ""; ?>
                >
                    Parent
                </option>

            </select>

        </div>

        <button type="submit">
            Create Account
        </button>

    </form>

    <div class="login-link">

        Already have an account?

        <a href="login.php">
            Login
        </a>

    </div>

</div>

</body>

</html>
