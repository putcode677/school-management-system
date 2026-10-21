<?php

function loadDatabaseEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException("Unable to read database configuration.");
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || str_starts_with($line, "#")) {
            continue;
        }

        $separator = strpos($line, "=");
        if ($separator === false) {
            throw new RuntimeException("Invalid database configuration entry.");
        }

        $name = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        if ($name === "") {
            throw new RuntimeException("Invalid database configuration entry.");
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"')
                || ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false && !isset($_ENV[$name])) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

try {
    loadDatabaseEnv(__DIR__ . "/../.env");

    $requiredSettings = ["DB_HOST", "DB_PORT", "DB_NAME", "DB_USER", "DB_PASSWORD"];
    $settings = [];
    foreach ($requiredSettings as $setting) {
        $value = getenv($setting);
        if ($value === false && isset($_ENV[$setting])) {
            $value = $_ENV[$setting];
        }

        if ($value === false) {
            throw new RuntimeException("Missing required setting: $setting");
        }

        $settings[$setting] = $value;
    }

    $pdo = new PDO(
        "pgsql:host={$settings["DB_HOST"]};port={$settings["DB_PORT"]};dbname={$settings["DB_NAME"]}",
        $settings["DB_USER"],
        $settings["DB_PASSWORD"],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (RuntimeException | PDOException $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    if (PHP_SAPI !== "cli") {
        http_response_code(500);
    }
    exit(PHP_SAPI === "cli"
        ? "Database initialization failed. Check server configuration and logs.\n"
        : "Service temporarily unavailable.");
}