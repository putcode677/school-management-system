
<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$message = "";
$student = null;

// Get student ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$student_id = (int) $_GET['id'];


// ===============================
// LOAD STUDENT
// ===============================

try {

    $stmt = $pdo->prepare("
        SELECT
            students.id,
            students.user_id,
            students.student_number,
            students.date_of_birth,
            students.gender,
            students.phone,
            students.address,
            students.admission_date,
            users.full_name,
            users.email
        FROM students
        INNER JOIN users
            ON students.user_id = users.id
        WHERE students.id = ?
        LIMIT 1
    ");

    $stmt->execute([$student_id]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: index.php");
        exit();
    }

} catch (PDOException $e) {

    $message = "Database Error: " . $e->getMessage();
}


// ===============================
// UPDATE STUDENT
// ===============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $student_number = trim($_POST['student_number'] ?? '');

    $date_of_birth = !empty($_POST['date_of_birth'])
        ? $_POST['date_of_birth']
        : null;

    $gender = !empty($_POST['gender'])
        ? $_POST['gender']
        : null;

    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $admission_date = !empty($_POST['admission_date'])
        ? $_POST['admission_date']
        : null;


    // Required fields
    if (
        empty($full_name) ||
        empty($email) ||
        empty($student_number)
    ) {

        $message = "Please fill in all required fields.";

    } else {

        try {

            // ===============================
            // CHECK EMAIL
            // ===============================

            $checkEmail = $pdo->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                AND id != ?
                LIMIT 1
            ");

            $checkEmail->execute([
                $email,
                $student['user_id']
            ]);

            if ($checkEmail->fetch()) {

                $message = "Email already exists.";

            } else {

                // ===============================
                // CHECK STUDENT NUMBER
                // ===============================

                $checkStudent = $pdo->prepare("
                    SELECT id
                    FROM students
                    WHERE student_number = ?
                    AND id != ?
                    LIMIT 1
                ");

                $checkStudent->execute([
                    $student_number,
                    $student_id
                ]);

                if ($checkStudent->fetch()) {

                    $message = "Student number already exists.";

                } else {

                    // ===============================
                    // START TRANSACTION
                    // ===============================

                    $pdo->beginTransaction();


                    // ===============================
                    // UPDATE USER
                    // ===============================

                    if (!empty($password)) {

                        $hashedPassword = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $userStmt = $pdo->prepare("
                            UPDATE users
                            SET
                                full_name = ?,
                                email = ?,
                                password = ?
                            WHERE id = ?
                        ");

                        $userStmt->execute([
                            $full_name,
                            $email,
                            $hashedPassword,
                            $student['user_id']
                        ]);

                    } else {

                        $userStmt = $pdo->prepare("
                            UPDATE users
                            SET
                                full_name = ?,
                                email = ?
                            WHERE id = ?
                        ");

                        $userStmt->execute([
                            $full_name,
                            $email,
                            $student['user_id']
                        ]);
                    }


                    // ===============================
                    // UPDATE STUDENT
                    // ===============================

                    $studentStmt = $pdo->prepare("
                        UPDATE students
                        SET
                            student_number = ?,
                            date_of_birth = ?,
                            gender = ?,
                            phone = ?,
                            address = ?,
                            admission_date = ?
                        WHERE id = ?
                    ");

                    $studentStmt->execute([
                        $student_number,
                        $date_of_birth,
                        $gender,
                        $phone,
                        $address,
                        $admission_date,
                        $student_id
                    ]);


                    // ===============================
                    // COMMIT
                    // ===============================

                    $pdo->commit();

                    header("Location: index.php");
                    exit();
                }
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = "Database Error: " . $e->getMessage();
        }
    }


    // Keep entered values after validation error
    $student['full_name'] = $full_name;
    $student['email'] = $email;
    $student['student_number'] = $student_number;
    $student['date_of_birth'] = $date_of_birth;
    $student['gender'] = $gender;
    $student['phone'] = $phone;
    $student['address'] = $address;
    $student['admission_date'] = $admission_date;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Student | School Management System</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #dbeafe;

            color: #243b53;

            min-height: 100vh;

            padding: 30px;
        }


        .container {

            max-width: 1000px;

            margin: auto;
        }


        /* ===============================
           HEADER
        =============================== */

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

            font-size: 14px;

            color: #627d98;
        }


        /* ===============================
           BUTTONS
        =============================== */

        .btn,
        button {

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


        /* ===============================
           MESSAGE
        =============================== */

        .error {

            background: #ffe5e5;

            color: #b42318;

            padding: 14px 18px;

            border-radius: 14px;

            margin-bottom: 22px;

            font-size: 14px;

            box-shadow:
                5px 5px 10px #b8c9dc,
                -5px -5px 10px #ffffff;
        }


        /* ===============================
           CARD
        =============================== */

        .card {

            background: #dbeafe;

            border-radius: 25px;

            padding: 32px;

            box-shadow:
                12px 12px 25px #b8c9dc,
                -12px -12px 25px #ffffff;
        }


        /* ===============================
           SECTIONS
        =============================== */

        .section {

            margin-bottom: 30px;
        }


        .section-title {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 20px;
        }


        .section-icon {

            width: 42px;

            height: 42px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #dbeafe;

            color: #1769aa;

            font-size: 19px;

            box-shadow:
                5px 5px 10px #b8c9dc,
                -5px -5px 10px #ffffff;
        }


        .section-title h2 {

            font-size: 18px;

            color: #174a7e;
        }


        .section-title p {

            font-size: 12px;

            color: #718096;

            margin-top: 3px;
        }


        /* ===============================
           FORM
        =============================== */

        .form-row {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 22px;

            margin-bottom: 20px;
        }


        .form-group {

            margin-bottom: 20px;
        }


        .form-row .form-group {

            margin-bottom: 0;
        }


        label {

            display: block;

            margin-bottom: 9px;

            font-size: 13px;

            font-weight: 600;

            color: #334e68;
        }


        input,
        select {

            width: 100%;

            border: none;

            outline: none;

            background: #dbeafe;

            color: #243b53;

            padding: 14px 16px;

            border-radius: 13px;

            font-size: 14px;

            box-shadow:
                inset 5px 5px 10px #b8c9dc,
                inset -5px -5px 10px #ffffff;

            transition: 0.2s ease;
        }


        input:focus,
        select:focus {

            box-shadow:
                inset 3px 3px 7px #b8c9dc,
                inset -3px -3px 7px #ffffff;
        }


        input::placeholder {

            color: #829ab1;
        }


        /* ===============================
           PASSWORD NOTE
        =============================== */

        .password-note {

            margin-top: 8px;

            font-size: 12px;

            color: #718096;
        }


        /* ===============================
           ACTIONS
        =============================== */

        .actions {

            display: flex;

            justify-content: flex-end;

            align-items: center;

            gap: 15px;

            margin-top: 30px;

            padding-top: 25px;

            border-top:
                1px solid
                rgba(255,255,255,0.5);
        }


        .required {

            color: #d64545;
        }


        /* ===============================
           RESPONSIVE
        =============================== */

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


            .form-row .form-group {

                margin-bottom: 20px;
            }


            .actions {

                flex-direction: column-reverse;

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

            <h1>Edit Student</h1>

            <p>
                Update student account and profile information.
            </p>

        </div>


        <a
            href="index.php"
            class="btn btn-secondary"
        >
            ← Back to Students
        </a>

    </div>


    <!-- ERROR -->

    <?php if (!empty($message)): ?>

        <div class="error">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- CARD -->

    <div class="card">


        <form method="POST">


            <!-- PERSONAL INFORMATION -->

            <div class="section">

                <div class="section-title">

                    <div class="section-icon">
                        👤
                    </div>

                    <div>

                        <h2>
                            Personal Information
                        </h2>

                        <p>
                            Basic information about the student
                        </p>

                    </div>

                </div>


                <div class="form-group">

                    <label for="full_name">

                        Full Name
                        <span class="required">*</span>

                    </label>


                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= htmlspecialchars($student['full_name']) ?>"
                        placeholder="Enter student's full name"
                        required
                    >

                </div>


                <div class="form-row">


                    <div class="form-group">

                        <label for="date_of_birth">
                            Date of Birth
                        </label>


                        <input
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            value="<?= htmlspecialchars($student['date_of_birth'] ?? '') ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label for="gender">
                            Gender
                        </label>


                        <select
                            id="gender"
                            name="gender"
                        >

                            <option value="">
                                Select Gender
                            </option>


                            <option
                                value="Male"
                                <?= $student['gender'] === 'Male' ? 'selected' : '' ?>
                            >
                                Male
                            </option>


                            <option
                                value="Female"
                                <?= $student['gender'] === 'Female' ? 'selected' : '' ?>
                            >
                                Female
                            </option>


                            <option
                                value="Other"
                                <?= $student['gender'] === 'Other' ? 'selected' : '' ?>
                            >
                                Other
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <!-- CONTACT INFORMATION -->

            <div class="section">

                <div class="section-title">

                    <div class="section-icon">
                        📞
                    </div>

                    <div>

                        <h2>
                            Contact Information
                        </h2>

                        <p>
                            Student contact and address details
                        </p>

                    </div>

                </div>


                <div class="form-row">


                    <div class="form-group">

                        <label for="email">

                            Email
                            <span class="required">*</span>

                        </label>


                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($student['email']) ?>"
                            placeholder="student@example.com"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>


                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars($student['phone'] ?? '') ?>"
                            placeholder="07XXXXXXXX"
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="address">
                        Address
                    </label>


                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="<?= htmlspecialchars($student['address'] ?? '') ?>"
                        placeholder="Enter student's address"
                    >

                </div>

            </div>


            <!-- ACADEMIC INFORMATION -->

            <div class="section">

                <div class="section-title">

                    <div class="section-icon">
                        🎓
                    </div>

                    <div>

                        <h2>
                            Academic Information
                        </h2>

                        <p>
                            Student identification and admission details
                        </p>

                    </div>

                </div>


                <div class="form-row">


                    <div class="form-group">

                        <label for="student_number">

                            Student Number
                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            id="student_number"
                            name="student_number"
                            value="<?= htmlspecialchars($student['student_number']) ?>"
                            placeholder="e.g. STD001"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="admission_date">
                            Admission Date
                        </label>


                        <input
                            type="date"
                            id="admission_date"
                            name="admission_date"
                            value="<?= htmlspecialchars($student['admission_date'] ?? '') ?>"
                        >

                    </div>

                </div>

            </div>


            <!-- ACCOUNT INFORMATION -->

            <div class="section">

                <div class="section-title">

                    <div class="section-icon">
                        🔐
                    </div>

                    <div>

                        <h2>
                            Account Information
                        </h2>

                        <p>
                            Change the student's login password
                        </p>

                    </div>

                </div>


                <div class="form-group">

                    <label for="password">
                        New Password
                    </label>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Leave empty to keep current password"
                    >


                    <div class="password-note">

                        Leave this field empty if you do not want
                        to change the student's password.

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


                <button type="submit">

                    💾 Update Student

                </button>


            </div>


        </form>

    </div>

</div>


</body>

</html>

