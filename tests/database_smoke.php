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
        if (!str_starts_with((string) $e->getCode(), "23")) {
            throw new RuntimeException(
                "Unexpected database error during constraint test: $name",
                0,
                $e
            );
        }
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

    $attendanceSession = $pdo->prepare(
        "INSERT INTO attendance_sessions
            (class_id, attendance_date, created_by, updated_by)
         VALUES (?, CURRENT_DATE, ?, ?)
         RETURNING id"
    );
    $attendanceSession->execute([$classId, $teacherId, $teacherId]);
    $attendanceSessionId = (int) $attendanceSession->fetchColumn();

    $attendanceRecord = $pdo->prepare(
        "INSERT INTO attendance_records
            (session_id, student_id, status, recorded_by)
         VALUES (?, ?, 'present', ?)
         RETURNING id"
    );
    $attendanceRecord->execute([$attendanceSessionId, $studentId, $teacherId]);
    $attendanceRecordId = (int) $attendanceRecord->fetchColumn();

    $attendanceChange = $pdo->prepare(
        "INSERT INTO attendance_changes
            (attendance_record_id, previous_status, new_status, changed_by)
         VALUES (?, 'present', 'late', ?)"
    );
    $attendanceChange->execute([$attendanceRecordId, $teacherId]);

    $attendanceUpdate = $pdo->prepare(
        "UPDATE attendance_records SET status = 'late' WHERE id = ?"
    );
    $attendanceUpdate->execute([$attendanceRecordId]);

    $attendanceRead = $pdo->prepare(
        "SELECT ar.status, ac.previous_status, ac.new_status
         FROM attendance_records ar
         JOIN attendance_sessions s ON s.id = ar.session_id
         JOIN attendance_changes ac ON ac.attendance_record_id = ar.id
         WHERE ar.id = ? AND s.class_id = ? AND ar.student_id = ?"
    );
    $attendanceRead->execute([$attendanceRecordId, $classId, $studentId]);
    $attendanceResult = $attendanceRead->fetch(PDO::FETCH_ASSOC);
    if (
        !$attendanceResult
        || $attendanceResult["status"] !== "late"
        || $attendanceResult["previous_status"] !== "present"
        || $attendanceResult["new_status"] !== "late"
    ) {
        throw new RuntimeException("Attendance records or correction history did not persist.");
    }
    echo "PASS: attendance records and correction history\n";

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

    expectConstraintViolation(
        $pdo,
        static function () use ($pdo, $attendanceSessionId, $studentId, $teacherId): void {
            $stmt = $pdo->prepare(
                "INSERT INTO attendance_records
                    (session_id, student_id, status, recorded_by)
                 VALUES (?, ?, 'unknown', ?)"
            );
            $stmt->execute([$attendanceSessionId, $studentId, $teacherId]);
        },
        "attendance status validation"
    );

    expectConstraintViolation(
        $pdo,
        static function () use ($pdo, $studentId): void {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
        },
        "preserve student attendance history"
    );

    expectConstraintViolation(
        $pdo,
        static function () use ($pdo, $classId): void {
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->execute([$classId]);
        },
        "preserve class attendance history"
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
