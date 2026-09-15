<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $student_number = trim($_POST['student_number']);
    $date_of_birth = !empty($_POST['date_of_birth'])
        ? $_POST['date_of_birth']
        : null;

    $gender = !empty($_POST['gender'])
        ? $_POST['gender']
        : null;

    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $admission_date = !empty($_POST['admission_date'])
        ? $_POST['admission_date']
        : null;


    if (
        empty($full_name) ||
        empty($email) ||
        empty($password) ||
        empty($student_number)
    ) {

        $message = "Please fill in all required fields.";

    } else {

        try {

            // Check email
            $checkEmail = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );

            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {

                $message = "Email already exists.";

            } else {

                // Check student number
                $checkStudent = $pdo->prepare(
                    "SELECT id FROM students WHERE student_number = ?"
                );

                $checkStudent->execute([$student_number]);

                if ($checkStudent->fetch()) {

                    $message = "Student number already exists.";

                } else {

                    $pdo->beginTransaction();

                    // Hash password
                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                    // Insert user
                    $userSql = "INSERT INTO users
                                (
                                    full_name,
                                    email,
                                    password,
                                    role,
                                    status
                                )
                                VALUES
                                (?, ?, ?, 'student', 'active')";

                    $userStmt = $pdo->prepare($userSql);

                    $userStmt->execute([
                        $full_name,
                        $email,
                        $hashedPassword
                    ]);

                    $user_id = $pdo->lastInsertId();


                    // Insert student
                    $studentSql = "INSERT INTO students
                                   (
                                       user_id,
                                       student_number,
                                       date_of_birth,
                                       gender,
                                       phone,
                                       address,
                                       admission_date
                                   )
                                   VALUES
                                   (?, ?, ?, ?, ?, ?, ?)";

                    $studentStmt = $pdo->prepare($studentSql);

                    $studentStmt->execute([
                        $user_id,
                        $student_number,
                        $date_of_birth,
                        $gender,
                        $phone,
                        $address,
                        $admission_date
                    ]);


                    $pdo->commit();

                    header("Location: index.php");
                    exit();
                }
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = "Something went wrong. Please try again.";
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

    <title>Add Student | School Management System</title>

    <link rel="stylesheet"
          href="../../assets/css/style.css">

</head>

<body>

<div class="container">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Add New Student</h1>

            <p>
                Create a new student account and profile.
            </p>

        </div>

        <a href="index.php" class="btn btn-secondary">
            ← Back
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

            <!-- PERSONAL INFORMATION -->

            <h2>Personal Information</h2>

            <div class="form-group">

                <label for="full_name">
                    Full Name *
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
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

                        <option value="Male">
                            Male
                        </option>

                        <option value="Female">
                            Female
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>

            </div>


            <!-- CONTACT INFORMATION -->

            <h2>Contact Information</h2>

            <div class="form-row">

                <div class="form-group">

                    <label for="email">
                        Email *
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
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
                    placeholder="Enter student's address"
                >

            </div>


            <!-- ACADEMIC INFORMATION -->

            <h2>Academic Information</h2>

            <div class="form-row">

                <div class="form-group">

                    <label for="student_number">
                        Student Number *
                    </label>

                    <input
                        type="text"
                        id="student_number"
                        name="student_number"
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
                    >

                </div>

            </div>


            <!-- ACCOUNT INFORMATION -->

            <h2>Account Information</h2>

            <div class="form-group">

                <label for="password">
                    Temporary Password *
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter temporary password"
                    required
                >

            </div>


            <!-- BUTTONS -->

            <div class="actions">

                <button type="submit">
                    Add Student
                </button>

                <a href="index.php"
                   class="btn btn-secondary">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>