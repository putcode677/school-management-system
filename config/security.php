<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax",
    ]);
    session_start();
}

function csrfToken(): string
{
    if (!isset($_SESSION["csrf_token"]) || !is_string($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8")
        . '">';
}

function isValidCsrfToken(mixed $token): bool
{
    return is_string($token)
        && hash_equals(csrfToken(), $token);
}

function requireCsrfToken(mixed $token): void
{
    if (!isValidCsrfToken($token)) {
        http_response_code(419);
        exit("Your session has expired. Reload the page and try again.");
    }
}

function passwordIsAcceptable(string $password): bool
{
    return strlen($password) >= 12;
}

function enforceRateLimit(string $key, int $limit, int $windowSeconds): bool
{
    $now = time();
    $bucket = $_SESSION["rate_limits"][$key] ?? [];
    $bucket = array_values(array_filter(
        $bucket,
        static fn (mixed $timestamp): bool =>
            is_int($timestamp) && $timestamp > $now - $windowSeconds
    ));

    if (count($bucket) >= $limit) {
        $_SESSION["rate_limits"][$key] = $bucket;
        return false;
    }

    $bucket[] = $now;
    $_SESSION["rate_limits"][$key] = $bucket;
    return true;
}
