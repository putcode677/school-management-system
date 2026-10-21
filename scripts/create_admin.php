<?php

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("CLI access required.");
}

if (!function_exists("stream_isatty") || !stream_isatty(STDIN)) {
    fwrite(STDERR, "Run this command in an interactive terminal.\n");
    exit(1);
}

require_once __DIR__ . "/../config/database.php";

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    return trim((string) fgets(STDIN));
}

function promptHidden(string $label): string
{
    fwrite(STDOUT, $label);
    shell_exec("stty -echo");

    try {
        $value = trim((string) fgets(STDIN));
    } finally {
        shell_exec("stty echo");
        fwrite(STDOUT, PHP_EOL);
    }

    return $value;
}

$existingAdmin = $pdo->query(
    "SELECT 1 FROM users WHERE role = 'admin' LIMIT 1"
)->fetchColumn();

if ($existingAdmin) {
    fwrite(STDERR, "An administrator account already exists.\n");
    exit(1);
}

$fullName = prompt("Administrator full name: ");
$email = prompt("Administrator email: ");
$password = promptHidden("Administrator password (minimum 12 characters): ");

if ($fullName === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Enter a name and a valid email address.\n");
    exit(1);
}

if (strlen($password) < 12) {
    fwrite(STDERR, "The password must be at least 12 characters.\n");
    exit(1);
}

$insert = $pdo->prepare(
    "INSERT INTO users (full_name, email, password, role, status)
     VALUES (?, ?, ?, 'admin', 'active')"
);
$insert->execute([
    $fullName,
    $email,
    password_hash($password, PASSWORD_DEFAULT),
]);

echo "Administrator account created.\n";
