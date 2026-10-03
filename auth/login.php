<?php

session_start();

require_once "../config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

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

        /* =====================================================
           RESET
        ===================================================== */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        /* =====================================================
           BODY
        ===================================================== */

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


        /* =====================================================
           BACKGROUND DECORATION
        ===================================================== */

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


        /* =====================================================
           LOGIN WRAPPER
        ===================================================== */

        .login-wrapper {

            width: 100%;
            max-width: 620px;

            position: relative;

            padding-top: 75px;
        }


        /* =====================================================
           LOGO
        ===================================================== */

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


        /* =====================================================
           LOGIN CARD
        ===================================================== */

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


        /* =====================================================
           HEADINGS
        ===================================================== */

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


        /* =====================================================
           MESSAGE
        ===================================================== */

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


        .success {

            background: #e2f5eb;

            color: #18764a;
        }


        /* =====================================================
           FORM GROUP
        ===================================================== */

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


        /* =====================================================
           INPUT WRAPPER
        ===================================================== */

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

            padding:
                0 50px;

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


        /* =====================================================
           PASSWORD TOGGLE
        ===================================================== */

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


        /* =====================================================
           LOGIN BUTTON
        ===================================================== */

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


        /* =====================================================
           FORGOT PASSWORD
        ===================================================== */

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


        /* =====================================================
           DIVIDER
        ===================================================== */

        .divider {

            display: flex;

            align-items: center;

            gap: 18px;

            margin: 32px 0 25px;

            color: #7891b4;

            font-size: 14px;
        }


        .divider::before,
        .divider::after {

            content: "";

            height: 1px;

            flex: 1;

            background: #c5d3e5;

            box-shadow:
                0 1px 1px #ffffff;
        }


        /* =====================================================
           REGISTER
        ===================================================== */

        .register-text {

            text-align: center;

            color: #7188a9;

            font-size: 15px;

            margin-bottom: 15px;
        }


        .register-button {

            width: 100%;

            height: 54px;

            display: flex;

            justify-content: center;

            align-items: center;

            border-radius: 28px;

            background: #e4ebf5;

            color: #216ce5;

            text-decoration: none;

            font-size: 15px;

            font-weight: 600;

            box-shadow:

                7px 7px 15px
                rgba(163, 177, 198, 0.45),

                -7px -7px 15px
                rgba(255, 255, 255, 0.9);

            transition: 0.2s ease;
        }


        .register-button:hover {

            transform: translateY(-2px);
        }


        .register-button:active {

            transform: translateY(1px);

            box-shadow:

                inset 4px 4px 8px
                rgba(163, 177, 198, 0.4),

                inset -4px -4px 8px
                rgba(255, 255, 255, 0.8);
        }


        /* =====================================================
           MOBILE
        ===================================================== */

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

</head>


<body>


<div class="login-wrapper">


    <!-- LOGO -->

    <div class="logo-container">

        <div class="logo">
            🎓
        </div>

    </div>


    <!-- LOGIN CARD -->

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


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <div class="input-wrapper">

                    <span class="input-icon">
                        ✉
                    </span>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                        required
                    >

                </div>

            </div>


            <!-- PASSWORD -->

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
                        id="password"
                        placeholder="Enter your password"
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


            <!-- LOGIN -->

            <button
                type="submit"
                class="login-button"
            >
                ↪ &nbsp; Login
            </button>


            <!-- FORGOT PASSWORD -->

            <div class="forgot-password">

                <a href="forgot_password.php">
                    Forgot Password?
                </a>

            </div>


            <!-- DIVIDER -->

            <div class="divider">
                <span>or</span>
            </div>


            <!-- REGISTER -->

            <div class="register-text">

                Don't have an account?

            </div>



        </form>


    </div>


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
