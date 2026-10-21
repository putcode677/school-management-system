<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("CLI access required.");
}

require_once __DIR__ . "/../config/database.php";

function expectConstraintViolation(PDO $pdo, callable $operation, string $name): void
{
    $pdo->exec("SAVEPOINT constraint_check");
    try {
        $operation();
    } catch (PDOException $e) {
        $pdo->exec("ROLLBACK TO SAVEPOINT constraint_check");
        echo "PASS: $name\n";
        return;
    }

    $pdo->exec("ROLLBACK TO SAVEPOINT constraint_check");
    throw new RuntimeException("Expected database constraint: $name");
}

try {
    $pdo->beginTransaction();

    $suffix = bin2hex(random_bytes(8));
    $email = "smoke-$suffix@example.invalid";
    $userInsert = $pdo->prepare(
        "INSERT INTO users (full_name, email, password, role)
         VALUES (?, ?, ?, ?)
         RETURNING id"
    );

    $userInsert->execute(["Smoke Student", $email, "test-hash", "student"]);
    $studentUserId = (int) $userInsert->fetchColumn();
    $userInsert->execute(["Smoke Teacher", "teacher-$suffix@example.invalid", "test-hash", "teacher"]);
    $teacherUserId = (int) $userInsert->fetchColumn();
    $userInsert->execute(["Smoke Parent", "parent-$suffix@example.invalid", "test-hash", "parent"]);
    $parentUserId = (int) $userInsert->fetchColumn();

    $profile = $pdo->prepare(
        "INSERT INTO students (user_id, student_number) VALUES (?, ?) RETURNING id"
    );
    $profile->execute([$studentUserId, "S$suffix"]);
    $studentId = (int) $profile->fetchColumn();

    $profile = $pdo->prepare(
        "INSERT INTO teachers (user_id, employee_number) VALUES (?, ?) RETURNING id"
    );
    $profile->execute([$teacherUserId, "T$suffix"]);
    $teacherId = (int) $profile->fetchColumn();

    $profile = $pdo->prepare(
        "INSERT INTO parents (user_id, parent_number) VALUES (?, ?) RETURNING id"
    );
    $profile->execute([$parentUserId, "P$suffix"]);
    $parentId = (int) $profile->fetchColumn();

    $profile = $pdo->prepare(
        "INSERT INTO classes (class_name, class_code) VALUES (?, ?) RETURNING id"
    );
    $profile->execute(["Smoke Class", "C$suffix"]);
    $classId = (int) $profile->fetchColumn();

    $profile = $pdo->prepare(
        "INSERT INTO subjects (subject_name, subject_code) VALUES (?, ?) RETURNING id"
    );
    $profile->execute(["Smoke Subject", "SUB$suffix"]);
    $subjectId = (int) $profile->fetchColumn();

    $link = $pdo->prepare(
        "INSERT INTO student_classes (student_id, class_id) VALUES (?, ?)"
    );
    $link->execute([$studentId, $classId]);

    $link = $pdo->prepare(
        "INSERT INTO student_parents (student_id, parent_id, relationship)
         VALUES (?, ?, 'Guardian')"
    );
    $link->execute([$studentId, $parentId]);

    $link = $pdo->prepare(
        "INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)"
    );
    $link->execute([$teacherId, $classId]);

    $link = $pdo->prepare(
        "INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)"
    );
    $link->execute([$teacherId, $subjectId]);

    $link = $pdo->prepare(
        "INSERT INTO password_resets (user_id, token, expires_at)
         VALUES (?, ?, CURRENT_TIMESTAMP + INTERVAL '30 minutes')"
    );
    $link->execute([$studentUserId, bin2hex(random_bytes(32))]);

    $join = $pdo->prepare(
        "SELECT COUNT(*)
         FROM student_parents sp
         JOIN students s ON s.id = sp.student_id
         JOIN users u ON u.id = s.user_id
         JOIN student_classes sc ON sc.student_id = s.id
         JOIN classes c ON c.id = sc.class_id
         WHERE sp.parent_id = ? AND u.email = ? AND c.id = ?"
    );
    $join->execute([$parentId, $email, $classId]);
    if ((int) $join->fetchColumn() !== 1) {
        throw new RuntimeException("Core student-parent-class relationships did not join.");
    }
    echo "PASS: core account and school relationships\n";

    expectConstraintViolation(
        $pdo,
        static function () use ($userInsert, $email): void {
            $userInsert->execute(["Duplicate Email", strtoupper($email), "test-hash", "student"]);
        },
        "case-insensitive unique user email"
    );

    expectConstraintViolation(
        $pdo,
        static function () use ($pdo, $studentUserId, $suffix): void {
            $stmt = $pdo->prepare(
                "INSERT INTO students (user_id, student_number) VALUES (?, ?)"
            );
            $stmt->execute([$studentUserId, "DUP$suffix"]);
        },
        "one student profile per account"
    );

    expectConstraintViolation(
        $pdo,
        static function () use ($pdo, $studentId, $classId): void {
            $stmt = $pdo->prepare(
                "INSERT INTO student_classes (student_id, class_id) VALUES (?, ?)"
            );
            $stmt->execute([$studentId, $classId]);
        },
        "duplicate class membership prevention"
    );

    $pdo->rollBack();
    echo "Database smoke tests passed.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Database smoke test failed: {$e->getMessage()}\n");
    exit(1);
}
