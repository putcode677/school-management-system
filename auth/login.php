<?php

session_start();

require_once "../config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";
        $message_type = "error";

    } else {

        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user) {

            $message = "Invalid email or password.";
            $message_type = "error";

        } elseif (!password_verify($password, $user["password"])) {

            $message = "Invalid email or password.";
            $message_type = "error";

        } elseif ($user["status"] !== "active") {

            $message = "Your account is inactive. Please contact the administrator.";
            $message_type = "error";

        } else {

            // Create login session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            // Redirect according to role
            switch ($user["role"]) {

                case "admin":
                    header("Location: ../dashboard/admin.php");
                    exit;

                case "teacher":
                    header("Location: ../dashboard/teacher.php");
                    exit;

                case "student":
                    header("Location: ../dashboard/student.php");
                    exit;

                case "parent":
                    header("Location: ../dashboard/parent.php");
                    exit;

                default:
                    $message = "Invalid user role.";
                    $message_type = "error";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - School Management System</title>

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

        .login-container {
            width: 400px;
            background: white;

            padding: 30px;

            border-radius: 10px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        label {
            display: block;

            margin-top: 15px;
            margin-bottom: 5px;

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
            padding: 10px;

            margin-bottom: 15px;

            border-radius: 5px;

            text-align: center;
        }

        .success {
            background: #d1fae5;
            color: #065f46;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .register-link {
            text-align: center;

            margin-top: 20px;
        }

        .register-link a {
            color: #2563eb;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="login-container">

    <h2>School Management System</h2>

    <h3 style="text-align:center;">Login</h3>

    <?php if (!empty($message)): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="email">
            Email
        </label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email"
            required
        >

        <label for="password">
            Password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

    <div class="register-link">

        Don't have an account?

        <a href="register.php">
            Create Account
        </a>

    </div>

</div>

</body>

</html>