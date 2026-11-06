<?php

declare(strict_types=1);

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("CLI access required.\n");
}

require_once __DIR__ . "/../config/security.php";

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("FAIL: $message");
    }

    echo "PASS: $message\n";
}

$_SESSION = [];

$token = csrfToken();
assertTrue(strlen($token) === 64, "CSRF token has the expected length");
assertTrue(isValidCsrfToken($token), "generated CSRF token is valid");
assertTrue(!isValidCsrfToken(str_repeat("0", 64)), "invalid CSRF token is rejected");

assertTrue(!passwordIsAcceptable(str_repeat("a", 11)), "11-character passwords are rejected");
assertTrue(passwordIsAcceptable(str_repeat("a", 12)), "12-character passwords are accepted");

assertTrue(enforceRateLimit("security-test", 2, 900), "first rate-limit attempt is accepted");
assertTrue(enforceRateLimit("security-test", 2, 900), "second rate-limit attempt is accepted");
assertTrue(!enforceRateLimit("security-test", 2, 900), "attempts beyond the limit are rejected");

echo "Security helper tests passed.\n";
