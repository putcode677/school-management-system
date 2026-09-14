<?php

session_start();

require_once "../config/database.php";

$message = "";
$message_type = "";

$token = $_GET["token"] ?? "";

$user_id = null;

// Check if token exists
if (empty($token)) {

    $message = "Invalid password reset link.";
    $message_type = "error";

} else {

    // Find valid token
    $stmt = $pdo->prepare(
        "SELECT id, user_id, expires_at
         FROM password_resets
         WHERE token = ?
         LIMIT 1"
    );

    $stmt->execute([$token]);

    $reset = $stmt->fetch();

    if (!$reset) {

        $message = "Invalid or expired password reset link.";
        $message_type = "error";

    } elseif (strtotime($reset["expires_at"]) < time()) {

        // Delete expired token
        $delete = $pdo->prepare(
            "DELETE FROM password_resets
             WHERE id = ?"
        );

        $delete->execute([$reset["id"]]);

        $message = "This password reset link has expired.";
        $message_type = "error";

    } else {

        $user_id = $reset["user_id"];
    }
}


// Process new password
if ($_SERVER["REQUEST_METHOD"] === "POST" && $user_id !== null) {

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (empty($password) || empty($confirm_password)) {

        $message = "Please enter both password fields.";
        $message_type = "error";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } else {

        // Hash the new password
        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Update user password
        $update = $pdo->prepare(
            "UPDATE users
             SET password = ?
             WHERE id = ?"
        );

        $update->execute([
            $hashed_password,
            $user_id
        ]);

        // Delete used reset token
        $delete = $pdo->prepare(
            "DELETE FROM password_resets
             WHERE user_id = ?"
        );

        $delete->execute([$user_id]);

        $message =
            "Your password has been reset successfully. "
            . "You can now login with your new password.";

        $message_type = "success";

        // Prevent form from being submitted again
        $user_id = null;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Reset Password - School Management System</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            font-family: Arial, sans-serif;

            background: #f4f6f9;

            display: flex;
            justify-content: center;
            align-items: center;

            min-height: 100vh;
        }

        .container {
            width: 420px;

            background: white;

            padding: 30px;

            border-radius: 10px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;

            margin-bottom: 10px;
        }

        .description {
            text-align: center;

            color: #64748b;

            margin-bottom: 25px;
        }

        label {
            display: block;

            margin-top: 15px;

            margin-bottom: 7px;

            font-weight: bold;
        }

        input {
            width: 100%;

            padding: 12px;

            border: 1px solid #ccc;

            border-radius: 6px;

            font-size: 15px;
        }

        button {
            width: 100%;

            margin-top: 25px;

            padding: 12px;

            border: none;

            border-radius: 6px;

            background: #2563eb;

            color: white;

            font-size: 16px;

            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            padding: 12px;

            margin-bottom: 15px;

            border-radius: 6px;

            text-align: center;

            line-height: 1.5;
        }

        .success {
            background: #d1fae5;

            color: #065f46;
        }

        .error {
            background: #fee2e2;

            color: #991b1b;
        }

        .login-link {
            text-align: center;

            margin-top: 20px;
        }

        .login-link a {
            color: #2563eb;

            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Reset Password</h2>

    <p class="description">
        Create a new password for your account.
    </p>

    <?php if (!empty($message)): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <?php if ($user_id !== null): ?>

        <form method="POST">

            <label for="password">
                New Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter new password"
                minlength="8"
                required
            >

            <label for="confirm_password">
                Confirm New Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm new password"
                minlength="8"
                required
            >

            <button type="submit">
                Reset Password
            </button>

        </form>

    <?php endif; ?>


    <?php if ($user_id === null): ?>

        <div class="login-link">

            <a href="login.php">
                ← Back to Login
            </a>

        </div>

    <?php endif; ?>

</div>

</body>

</html>