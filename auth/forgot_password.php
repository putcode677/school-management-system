<?php

session_start();

require_once "../config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);

    if (empty($email)) {

        $message = "Please enter your email address.";
        $message_type = "error";

    } else {

        $stmt = $pdo->prepare(
            "SELECT id, full_name, email
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user) {

            $message = "No account found with that email address.";
            $message_type = "error";

        } else {

            /*
             * Password reset email will be added
             * in the next step.
             */

            $message = "Email found. Password reset is ready for the next step.";
            $message_type = "success";
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

    <title>Forgot Password - School Management System</title>

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
            width: 400px;
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

            margin-top: 20px;
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

        .back {
            text-align: center;
            margin-top: 20px;
        }

        .back a {
            color: #2563eb;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Forgot Password?</h2>

    <p class="description">
        Enter your email address to reset your password.
    </p>

    <?php if (!empty($message)): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <label for="email">
            Email Address
        </label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email"
            required
        >

        <button type="submit">
            Continue
        </button>

    </form>

    <div class="back">

        <a href="login.php">
            ← Back to Login
        </a>

    </div>

</div>

</body>

</html>