<?php
session_start();
require_once "../../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get student ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$student_id = (int) $_GET['id'];

$message = "";
$student = null;


// Get student information
$sql = "SELECT
            students.id,
            students.user_id,
            students.student_number,
            students.date_of_birth,
            students.gender,
            students.phone,
            students.address,
            students.admission_date,
            users.full_name,
            users.email,
            users.status
        FROM students
        INNER JOIN users
            ON students.user_id = users.id
        WHERE students.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);


// Student not found
if (!$student) {
    header("Location: index.php");
    exit();
}


// Update student
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
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

    $status = $_POST['status'];


    if (
        empty($full_name) ||
        empty($email) ||
        empty($student_number)
    ) {

        $message = "Please fill in all required fields.";

    } else {

        try {

            // Check email belonging to another user
            $checkEmail = $pdo->prepare(
                "SELECT id FROM users
                 WHERE email = ?
                 AND id != ?"
            );

            $checkEmail->execute([
                $email,
                $student['user_id']
            ]);

            if ($checkEmail->fetch()) {

                $message = "Email already belongs to another user.";

            } else {

                // Check student number
                $checkNumber = $pdo->prepare(
                    "SELECT id FROM students
                     WHERE student_number = ?
                     AND id != ?"
                );

                $checkNumber->execute([
                    $student_number,
                    $student_id
                ]);

                if ($checkNumber->fetch()) {

                    $message = "Student number already exists.";

                } else {

                    $pdo->beginTransaction();


                    // Update users table
                    $userSql = "UPDATE users
                                SET
                                    full_name = ?,
                                    email = ?,
                                    status = ?
                                WHERE id = ?";

                    $userStmt = $pdo->prepare($userSql);

                    $userStmt->execute([
                        $full_name,
                        $email,
                        $status,
                        $student['user_id']
                    ]);


                    // Update students table
                    $studentSql = "UPDATE students
                                   SET
                                       student_number = ?,
                                       date_of_birth = ?,
                                       gender = ?,
                                       phone = ?,
                                       address = ?,
                                       admission_date = ?
                                   WHERE id = ?";

                    $studentStmt = $pdo->prepare($studentSql);

                    $studentStmt->execute([
                        $student_number,
                        $date_of_birth,
                        $gender,
                        $phone,
                        $address,
                        $admission_date,
                        $student_id
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

    <title>Edit Student | School Management System</title>

    <link rel="stylesheet"
          href="../../assets/css/style.css">

</head>

<body>

<div class="container">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Edit Student</h1>

            <p>
                Update student information.
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


    <!-- FORM -->

    <div class="card">

        <form method="POST">

            <h2>Personal Information</h2>


            <div class="form-group">

                <label for="full_name">
                    Full Name *
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= htmlspecialchars(
                        $student['full_name']
                    ) ?>"
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
                        value="<?= htmlspecialchars(
                            $student['date_of_birth'] ?? ''
                        ) ?>"
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

                        <option value="Male"
                            <?= $student['gender'] === 'Male'
                                ? 'selected'
                                : '' ?>>
                            Male
                        </option>

                        <option value="Female"
                            <?= $student['gender'] === 'Female'
                                ? 'selected'
                                : '' ?>>
                            Female
                        </option>

                        <option value="Other"
                            <?= $student['gender'] === 'Other'
                                ? 'selected'
                                : '' ?>>
                            Other
                        </option>

                    </select>

                </div>

            </div>


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
                        value="<?= htmlspecialchars(
                            $student['email']
                        ) ?>"
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
                        value="<?= htmlspecialchars(
                            $student['phone'] ?? ''
                        ) ?>"
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
                    value="<?= htmlspecialchars(
                        $student['address'] ?? ''
                    ) ?>"
                >

            </div>


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
                        value="<?= htmlspecialchars(
                            $student['student_number']
                        ) ?>"
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
                        value="<?= htmlspecialchars(
                            $student['admission_date'] ?? ''
                        ) ?>"
                    >

                </div>

            </div>


            <h2>Account Status</h2>


            <div class="form-group">

                <label for="status">
                    Account Status
                </label>

                <select
                    id="status"
                    name="status"
                >

                    <option value="active"
                        <?= $student['status'] === 'active'
                            ? 'selected'
                            : '' ?>>
                        Active
                    </option>

                    <option value="inactive"
                        <?= $student['status'] === 'inactive'
                            ? 'selected'
                            : '' ?>>
                        Inactive
                    </option>

                </select>

            </div>


            <div class="actions">

                <button type="submit">
                    Save Changes
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