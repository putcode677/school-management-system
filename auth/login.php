<?php

session_start();

require_once "../config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $credential = trim($_POST["credential"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($credential) || empty($password)) {

        $message = "Please enter your login credential and password.";
        $message_type = "error";

    } else {

        /*
         * ADMIN
         * Login using email
         */
        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password, role, status
             FROM users
             WHERE email = ?
             AND role = 'admin'
             LIMIT 1"
        );

        $stmt->execute([$credential]);
        $user = $stmt->fetch();

        /*
         * STUDENT
         */
        if (!$user) {

            $stmt = $pdo->prepare(
                "SELECT
                    u.id,
                    u.full_name,
                    u.email,
                    u.password,
                    u.role,
                    u.status
                 FROM students s
                 INNER JOIN users u
                    ON u.id = s.user_id
                 WHERE s.student_number = ?
                 AND u.role = 'student'
                 LIMIT 1"
            );

            $stmt->execute([$credential]);
            $user = $stmt->fetch();
        }

        /*
         * TEACHER
         */
        if (!$user) {

            $stmt = $pdo->prepare(
                "SELECT
                    u.id,
                    u.full_name,
                    u.email,
                    u.password,
                    u.role,
                    u.status
                 FROM teachers t
                 INNER JOIN users u
                    ON u.id = t.user_id
                 WHERE t.employee_number = ?
                 AND u.role = 'teacher'
                 LIMIT 1"
            );

            $stmt->execute([$credential]);
            $user = $stmt->fetch();
        }

        /*
         * PARENT
         */
        if (!$user) {

            $stmt = $pdo->prepare(
                "SELECT
                    u.id,
                    u.full_name,
                    u.email,
                    u.password,
                    u.role,
                    u.status
                 FROM parents p
                 INNER JOIN users u
                    ON u.id = p.user_id
                 WHERE p.parent_number = ?
                 AND u.role = 'parent'
                 LIMIT 1"
            );

            $stmt->execute([$credential]);
            $user = $stmt->fetch();
        }

        /*
         * CHECK LOGIN
         */
        if (!$user) {

            $message = "Invalid login credential or password.";
            $message_type = "error";

        } elseif (!password_verify($password, $user["password"])) {

            $message = "Invalid login credential or password.";
            $message_type = "error";

        } elseif ($user["status"] !== "active") {

            $message = "Your account is inactive. Please contact the administrator.";
            $message_type = "error";

        } else {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

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
                    session_destroy();

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

```
<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Login - School Management System</title>

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

        padding: 30px;

        font-family:
            "Segoe UI",
            Arial,
            sans-serif;

        background:
            linear-gradient(
                135deg,
                #e8eef7 0%,
                #dce6f3 50%,
                #edf3fa 100%
            );

        color: #263d63;

        overflow-x: hidden;

        position: relative;
    }

    body::before {

        content: "";

        position: fixed;

        width: 350px;
        height: 350px;

        top: -170px;
        left: -150px;

        border-radius: 50%;

        background: #e3ebf6;

        box-shadow:
            25px 25px 50px rgba(163, 177, 198, 0.35),
            -20px -20px 50px rgba(255, 255, 255, 0.8);

        z-index: -1;
    }

    body::after {

        content: "";

        position: fixed;

        width: 250px;
        height: 250px;

        bottom: -120px;
        right: -100px;

        border-radius: 50%;

        background: #e3ebf6;

        box-shadow:
            20px 20px 40px rgba(163, 177, 198, 0.35),
            -20px -20px 40px rgba(255, 255, 255, 0.8);

        z-index: -1;
    }

    .login-wrapper {

        width: 100%;
        max-width: 620px;

        position: relative;

        padding-top: 75px;
    }

    .logo-container {

        position: absolute;

        top: 0;
        left: 50%;

        transform: translateX(-50%);

        width: 110px;
        height: 110px;

        border-radius: 50%;

        display: flex;
        justify-content: center;
        align-items: center;

        background: #e3ebf6;

        box-shadow:
            12px 12px 25px rgba(163, 177, 198, 0.55),
            -12px -12px 25px rgba(255, 255, 255, 0.9);

        z-index: 5;
    }

    .logo {

        font-size: 46px;

        color: #2878ed;

        filter:
            drop-shadow(
                3px 4px 4px
                rgba(68, 104, 160, 0.3)
            );
    }

    .login-container {

        width: 100%;

        padding: 65px 70px 55px;

        border-radius: 38px;

        background: rgba(232, 239, 248, 0.88);

        border: 1px solid rgba(255, 255, 255, 0.65);

        box-shadow:
            20px 20px 45px
            rgba(163, 177, 198, 0.55),

            -20px -20px 45px
            rgba(255, 255, 255, 0.95);

        backdrop-filter: blur(10px);

        -webkit-backdrop-filter: blur(10px);
    }

    h1 {

        text-align: center;

        color: #203a62;

        font-size: 34px;

        font-weight: 700;

        letter-spacing: -0.5px;

        margin-bottom: 8px;

        text-shadow:
            1px 1px 1px #ffffff;
    }

    .login-title {

        text-align: center;

        font-size: 25px;

        color: #6d87ad;

        font-weight: 500;

        margin-bottom: 30px;
    }

    .message {

        width: 100%;

        padding: 15px 18px;

        margin-bottom: 25px;

        border-radius: 22px;

        text-align: center;

        font-size: 14px;

        box-shadow:
            inset 4px 4px 8px
            rgba(163, 177, 198, 0.25),

            inset -4px -4px 8px
            rgba(255, 255, 255, 0.75);
    }

    .error {

        background: #f8e4e7;

        color: #c43d4c;
    }

    .form-group {

        margin-bottom: 22px;
    }

    label {

        display: block;

        margin-bottom: 9px;

        padding-left: 8px;

        color: #5c7599;

        font-size: 14px;

        font-weight: 600;
    }

    .input-wrapper {

        position: relative;
    }

    .input-icon {

        position: absolute;

        left: 20px;

        top: 50%;

        transform: translateY(-50%);

        font-size: 20px;

        color: #7591b9;

        pointer-events: none;
    }

    input {

        width: 100%;

        height: 58px;

        padding: 0 50px;

        border: none;

        outline: none;

        border-radius: 30px;

        background: #e4ebf5;

        color: #314d76;

        font-size: 15px;

        box-shadow:
            inset 6px 6px 12px
            rgba(163, 177, 198, 0.45),

            inset -6px -6px 12px
            rgba(255, 255, 255, 0.9);

        transition: 0.25s ease;
    }

    input::placeholder {

        color: #8098b9;
    }

    input:focus {

        box-shadow:
            inset 4px 4px 8px
            rgba(163, 177, 198, 0.4),

            inset -4px -4px 8px
            rgba(255, 255, 255, 0.9),

            0 0 0 3px
            rgba(45, 119, 239, 0.12);
    }

    .password-toggle {

        position: absolute;

        right: 20px;

        top: 50%;

        transform: translateY(-50%);

        border: none;

        background: transparent;

        color: #7591b9;

        font-size: 19px;

        cursor: pointer;

        padding: 5px;
    }

    .login-button {

        width: 100%;

        height: 58px;

        margin-top: 8px;

        border: none;

        border-radius: 30px;

        background:
            linear-gradient(
                135deg,
                #4f96ff,
                #246be5
            );

        color: white;

        font-size: 17px;

        font-weight: 600;

        cursor: pointer;

        box-shadow:
            8px 8px 18px
            rgba(76, 117, 178, 0.45),

            -5px -5px 12px
            rgba(255, 255, 255, 0.75);

        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }

    .login-button:hover {

        transform: translateY(-2px);

        box-shadow:
            10px 10px 22px
            rgba(76, 117, 178, 0.5),

            -5px -5px 12px
            rgba(255, 255, 255, 0.8);
    }

    .login-button:active {

        transform: translateY(1px);

        box-shadow:
            inset 4px 4px 8px
            rgba(25, 77, 170, 0.35),

            inset -4px -4px 8px
            rgba(255, 255, 255, 0.25);
    }

    .forgot-password {

        text-align: center;

        margin-top: 22px;
    }

    .forgot-password a {

        color: #216ce5;

        font-size: 14px;

        font-weight: 600;

        text-decoration: none;
    }

    .forgot-password a:hover {

        text-decoration: underline;
    }

    .login-help {

        text-align: center;

        margin-top: 28px;

        color: #7188a9;

        font-size: 13px;

        line-height: 1.6;
    }

    .login-help strong {

        color: #526f9b;
    }

    @media (max-width: 650px) {

        body {

            padding: 20px;
        }

        .login-wrapper {

            padding-top: 60px;
        }

        .login-container {

            padding: 55px 25px 35px;

            border-radius: 30px;
        }

        .logo-container {

            width: 90px;
            height: 90px;
        }

        .logo {

            font-size: 38px;
        }

        h1 {

            font-size: 25px;
        }

        .login-title {

            font-size: 21px;
        }
    }

</style>
```

</head>

<body>

<div class="login-wrapper">

```
<div class="logo-container">

    <div class="logo">
        🎓
    </div>

</div>

<div class="login-container">

    <h1>
        School Management System
    </h1>

    <div class="login-title">
        Login
    </div>

    <?php if (!empty($message)): ?>

        <div class="message <?php echo htmlspecialchars($message_type); ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label for="credential">
                Login ID / Email
            </label>

            <div class="input-wrapper">

                <span class="input-icon">
                    🔑
                </span>

                <input
                    type="text"
                    id="credential"
                    name="credential"
                    placeholder="STD001, EMP001, PAR001 or admin email"
                    value="<?php echo htmlspecialchars($_POST["credential"] ?? ""); ?>"
                    autocomplete="username"
                    required
                >

            </div>

        </div>

        <div class="form-group">

            <label for="password">
                Password
            </label>

            <div class="input-wrapper">

                <span class="input-icon">
                    🔒
                </span>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword()"
                    aria-label="Show password"
                >
                    👁
                </button>

            </div>

        </div>

        <button
            type="submit"
            class="login-button"
        >
            ↪ &nbsp; Login
        </button>

        <div class="forgot-password">

            <a href="forgot_password.php">
                Forgot Password?
            </a>

        </div>

        <div class="login-help">

            <strong>Student:</strong> Student Number
            &nbsp; | &nbsp;

            <strong>Teacher:</strong> Employee Number
            &nbsp; | &nbsp;

            <strong>Parent:</strong> Parent Number

        </div>

    </form>

</div>
```

</div>

<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const toggle =
        document.querySelector(".password-toggle");

    if (password.type === "password") {

        password.type = "text";

        toggle.textContent = "🙈";

    } else {

        password.type = "password";

        toggle.textContent = "👁";

    }

}

</script>

</body>

</html>
