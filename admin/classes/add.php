```php
<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$message = "";

$class_name = "";
$class_code = "";
$description = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $class_name = trim($_POST['class_name'] ?? '');
    $class_code = trim($_POST['class_code'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($class_name) || empty($class_code)) {

        $message = "Please fill in all required fields.";

    } else {

        try {

            // Check duplicate class code
            $check = $pdo->prepare(
                "SELECT id FROM classes WHERE class_code = ?"
            );

            $check->execute([$class_code]);

            if ($check->fetch()) {

                $message = "Class code already exists.";

            } else {

                // Insert class
                $sql = "INSERT INTO classes
                        (
                            class_name,
                            class_code,
                            description
                        )
                        VALUES
                        (?, ?, ?)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $class_name,
                    $class_code,
                    $description !== '' ? $description : null
                ]);

                // Return to classes list
                header("Location: index.php");
                exit();
            }

        } catch (PDOException $e) {

            $message = "Database Error: " . $e->getMessage();
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

    <title>Add Class | School Management System</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #dbeafe;
            color: #243b53;
            min-height: 100vh;
            padding: 30px;
        }

        .container {
            max-width: 850px;
            margin: auto;
        }

        /* HEADER */

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 30px;
            color: #174a7e;
            margin-bottom: 7px;
        }

        .page-header p {
            color: #627d98;
            font-size: 14px;
        }

        /* BUTTONS */

        .btn,
        button {
            display: inline-block;
            border: none;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 14px;
            padding: 13px 20px;
            transition: 0.2s ease;
        }

        .btn-secondary {
            background: #dbeafe;
            color: #174a7e;
            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        .btn-secondary:hover {
            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        .btn-dashboard {
            background: #dbeafe;
            color: #1769aa;
            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        .btn-dashboard:hover {
            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
        }

        button {
            background: #1769aa;
            color: white;
            box-shadow:
                7px 7px 14px #b8c9dc,
                -7px -7px 14px #ffffff;
        }

        button:hover {
            background: #155f99;
            transform: translateY(-1px);
        }

        /* MESSAGE */

        .error {
            background: #ffe5e5;
            color: #b42318;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 22px;
            box-shadow:
                5px 5px 10px #b8c9dc,
                -5px -5px 10px #ffffff;
            font-size: 14px;
        }

        /* CARD */

        .card {
            background: #dbeafe;
            border-radius: 25px;
            padding: 32px;
            box-shadow:
                12px 12px 25px #b8c9dc,
                -12px -12px 25px #ffffff;
        }

        /* SECTION */

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .section-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #1769aa;
            font-size: 20px;
            box-shadow:
                5px 5px 10px #b8c9dc,
                -5px -5px 10px #ffffff;
        }

        .section-title h2 {
            font-size: 19px;
            color: #174a7e;
        }

        .section-title p {
            font-size: 12px;
            color: #718096;
            margin-top: 3px;
        }

        /* FORM */

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-size: 13px;
            font-weight: 600;
            color: #334e68;
        }

        input,
        textarea {
            width: 100%;
            border: none;
            outline: none;
            background: #dbeafe;
            color: #243b53;
            padding: 14px 16px;
            border-radius: 13px;
            font-size: 14px;
            font-family: Arial, Helvetica, sans-serif;
            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;
            transition: 0.2s ease;
        }

        input:focus,
        textarea:focus {
            box-shadow:
                inset 3px 3px 7px #b8c9dc,
                inset -3px -3px 7px #ffffff;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        input::placeholder,
        textarea::placeholder {
            color: #829ab1;
        }

        /* ACTIONS */

        .actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.5);
        }

        .required {
            color: #d64545;
        }

        /* RESPONSIVE */

        @media (max-width: 700px) {

            body {
                padding: 18px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header h1 {
                font-size: 25px;
            }

            .card {
                padding: 22px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .actions button,
            .actions a {
                width: 100%;
                text-align: center;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1>Add New Class</h1>

            <p>
                Create a new class for the school.
            </p>

        </div>

        <a href="index.php" class="btn btn-secondary">
            ← Back to Classes
        </a>

    </div>


    <!-- ERROR MESSAGE -->

    <?php if (!empty($message)): ?>

        <div class="error">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- FORM CARD -->

    <div class="card">

        <form method="POST">

            <div class="section">

                <div class="section-title">

                    <div class="section-icon">
                        🎓
                    </div>

                    <div>

                        <h2>Class Information</h2>

                        <p>
                            Enter the basic information about this class.
                        </p>

                    </div>

                </div>


                <!-- CLASS NAME -->

                <div class="form-group">

                    <label for="class_name">
                        Class Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="class_name"
                        name="class_name"
                        value="<?= htmlspecialchars($class_name) ?>"
                        placeholder="e.g. Form One"
                        required
                    >

                </div>


                <!-- CLASS CODE + DESCRIPTION -->

                <div class="form-row">

                    <div class="form-group">

                        <label for="class_code">
                            Class Code
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="class_code"
                            name="class_code"
                            value="<?= htmlspecialchars($class_code) ?>"
                            placeholder="e.g. F1"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="description">
                            Description
                        </label>

                        <input
                            type="text"
                            id="description"
                            name="description"
                            value="<?= htmlspecialchars($description) ?>"
                            placeholder="e.g. First year students"
                        >

                    </div>

                </div>

            </div>


            <!-- ACTIONS -->

            <div class="actions">

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>


                <a
                    href="../../dashboard/admin.php"
                    class="btn btn-dashboard"
                >
                    ← Dashboard
                </a>


                <button type="submit">
                    ➕ Add Class
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>
```
