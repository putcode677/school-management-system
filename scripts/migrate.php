<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("CLI access required.");
}

require_once __DIR__ . "/../config/database.php";

$migrationFiles = glob(__DIR__ . "/../database/migrations/*.sql");
if ($migrationFiles === false || $migrationFiles === []) {
    fwrite(STDERR, "No database migrations were found.\n");
    exit(1);
}

sort($migrationFiles, SORT_STRING);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);

foreach ($migrationFiles as $migrationFile) {
    $version = basename($migrationFile);
    $check = $pdo->prepare(
        "SELECT 1 FROM schema_migrations WHERE version = ?"
    );
    $check->execute([$version]);
    if ($check->fetchColumn()) {
        echo "Already applied: $version\n";
        continue;
    }

    if ($version === "001_initial_schema.sql") {
        $existingTables = $pdo->query(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = current_schema()
               AND table_type = 'BASE TABLE'
               AND table_name IN (
                   'users', 'students', 'teachers', 'parents', 'classes',
                   'subjects', 'student_classes', 'student_parents',
                   'teacher_classes', 'teacher_subjects', 'password_resets'
               )
             ORDER BY table_name"
        )->fetchAll(PDO::FETCH_COLUMN);

        if ($existingTables !== []) {
            fwrite(
                STDERR,
                "Existing application tables found without migration history. "
                . "Back up and review the current schema before migrating.\n"
            );
            exit(1);
        }
    }

    $sql = file_get_contents($migrationFile);
    if ($sql === false) {
        fwrite(STDERR, "Unable to read migration: $version\n");
        exit(1);
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);

        $record = $pdo->prepare(
            "INSERT INTO schema_migrations (version) VALUES (?)"
        );
        $record->execute([$version]);
        $pdo->commit();

        echo "Applied: $version\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        fwrite(STDERR, "Migration failed ($version): {$e->getMessage()}\n");
        exit(1);
    }
}
