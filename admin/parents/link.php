<?php

session_start();

require_once "../../config/database.php";

/* Admin only */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$errors = [];
$success = "";

$parent_id = "";
$student_id = "";
$relationship = "";

/*
|--------------------------------------------------------------------------
| Load Parents
|--------------------------------------------------------------------------
*/

$parents = $pdo->query("
    SELECT
        parents.id,
        users.full_name,
        users.email
    FROM parents
    INNER JOIN users ON users.id = parents.user_id
    WHERE users.status = 'active'
    ORDER BY users.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Load Students
|--------------------------------------------------------------------------
*/

$students = $pdo->query("
    SELECT
        students.id,
        students.student_number,
        users.full_name
    FROM students
    INNER JOIN users ON users.id = students.user_id
    WHERE users.status = 'active'
    ORDER BY users.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Save Relationship
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $parent_id = (int) ($_POST["parent_id"] ?? 0);
    $student_id = (int) ($_POST["student_id"] ?? 0);
    $relationship = trim($_POST["relationship"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($parent_id <= 0) {
        $errors[] = "Please select a parent.";
    }

    if ($student_id <= 0) {
        $errors[] = "Please select a student.";
    }

    if ($relationship === "") {
        $errors[] = "Please select the relationship.";
    }

    /*
    |--------------------------------------------------------------------------
    | Check and Insert
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            /*
            | Check whether this parent and student are already linked
            */

            $check = $pdo->prepare("
                SELECT id
                FROM student_parents
                WHERE parent_id = ?
                AND student_id = ?
                LIMIT 1
            ");

            $check->execute([
                $parent_id,
                $student_id
            ]);

            if ($check->fetch()) {

                $errors[] = "This parent is already linked to this student.";

            } else {

                /*
                | Insert relationship
                */

                $stmt = $pdo->prepare("
                    INSERT INTO student_parents
                    (student_id, parent_id, relationship)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $student_id,
                    $parent_id,
                    $relationship
                ]);

                $success = "Parent linked to student successfully.";

                /*
                | Reset form
                */

                $parent_id = "";
                $student_id = "";
                $relationship = "";
            }

        } catch (PDOException $e) {

            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Link Parent to Student | School Management System</title>

    <link rel="stylesheet" href="../../assets/css/style.css">

</head>

<body>

<div class="container">

    <div class="page-header">

        <div>

            <h1>Link Parent to Student</h1>

            <p>
                Connect a parent or guardian to a student.
            </p>

        </div>

        <a href="index.php" class="btn btn-secondary">
            ← Back
        </a>

    </div>


    <!-- Errors -->

    <?php foreach ($errors as $error): ?>

        <div class="error">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>

    <?php endforeach; ?>


    <!-- Success -->

    <?php if ($success): ?>

        <div class="success">
            ✅ <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>


    <div class="card">

        <form method="POST">

            <h2>Relationship Information</h2>


            <!-- Parent -->

            <div class="form-group">

                <label for="parent_id">
                    Parent *
                </label>

                <select
                    id="parent_id"
                    name="parent_id"
                    required
                >

                    <option value="">
                        -- Select Parent --
                    </option>

                    <?php foreach ($parents as $parent): ?>

                        <option
                            value="<?= (int) $parent['id'] ?>"
                            <?= ((string) $parent_id === (string) $parent['id']) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($parent['full_name']) ?>
                            -
                            <?= htmlspecialchars($parent['email']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Student -->

            <div class="form-group">

                <label for="student_id">
                    Student *
                </label>

                <select
                    id="student_id"
                    name="student_id"
                    required
                >

                    <option value="">
                        -- Select Student --
                    </option>

                    <?php foreach ($students as $student): ?>

                        <option
                            value="<?= (int) $student['id'] ?>"
                            <?= ((string) $student_id === (string) $student['id']) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($student['full_name']) ?>
                            -
                            <?= htmlspecialchars($student['student_number']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Relationship -->

            <div class="form-group">

                <label for="relationship">
                    Relationship *
                </label>

                <select
                    id="relationship"
                    name="relationship"
                    required
                >

                    <option value="">
                        -- Select Relationship --
                    </option>

                    <option
                        value="Father"
                        <?= $relationship === "Father" ? "selected" : "" ?>
                    >
                        Father
                    </option>

                    <option
                        value="Mother"
                        <?= $relationship === "Mother" ? "selected" : "" ?>
                    >
                        Mother
                    </option>

                    <option
                        value="Guardian"
                        <?= $relationship === "Guardian" ? "selected" : "" ?>
                    >
                        Guardian
                    </option>

                    <option
                        value="Other"
                        <?= $relationship === "Other" ? "selected" : "" ?>
                    >
                        Other
                    </option>

                </select>

            </div>


            <div class="actions">

                <button type="submit">
                    🔗 Link Parent
                </button>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>


    <div style="margin-top:20px;">

        <a
            href="../../dashboard/admin.php"
            class="btn btn-secondary"
        >
            ← Dashboard
        </a>

    </div>

</div>

</body>

</html>
