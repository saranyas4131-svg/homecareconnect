<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a random CAPTCHA code.
 */
function generate_captcha_text(int $length = 5): string
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
}

/**
 * Save CAPTCHA for a specific registration type.
 */
function setCaptcha(string $type, string $code): void
{
    $type = ($type === 'provider') ? 'provider' : 'customer';

    $_SESSION['captcha_' . $type] = $code;
}

/**
 * Get CAPTCHA for a specific registration type.
 */
function getCaptcha(string $type): string
{
    $type = ($type === 'provider') ? 'provider' : 'customer';

    return $_SESSION['captcha_' . $type] ?? '';
}

/**
 * Verify CAPTCHA for a specific registration type.
 */
function verify_captcha(string $userInput, string $type = 'customer'): bool
{
    $type = ($type === 'provider') ? 'provider' : 'customer';

    $sessionKey = 'captcha_' . $type;

    if (empty($_SESSION[$sessionKey])) {
        return false;
    }

    $valid = strcasecmp(
        trim($userInput),
        $_SESSION[$sessionKey]
    ) === 0;

    // CAPTCHA can only be used once.
    unset($_SESSION[$sessionKey]);

    return $valid;
}