<?php

declare(strict_types=1);

function login_user_by_id(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }

    $loaded = true;
    $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
    if ($sessionUserId <= 0) {
        return null;
    }

    $resolvedUser = repo_find_user_by_id($sessionUserId);
    if (!is_array($resolvedUser)) {
        unset($_SESSION['user_id']);
        return null;
    }

    $user = $resolvedUser;
    return $user;
}

function is_logged_in(): bool
{
    return is_array(current_user());
}

function require_login(bool $asJson = false): void
{
    if (is_logged_in()) {
        return;
    }

    if ($asJson) {
        json_response([
            'success' => false,
            'message' => 'Anmeldung erforderlich.',
            'redirect_url' => 'login.php',
        ], 401);
    }

    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? 'dashboard.php');
    $path = basename((string) parse_url($requestUri, PHP_URL_PATH));
    if ($path !== '') {
        $_SESSION['redirect_after_login'] = $path;
    }

    flash_set('error', 'Bitte zuerst anmelden.');
    redirect('login.php');
}

function consume_post_login_redirect(string $fallback = 'dashboard.php'): string
{
    $target = (string) ($_SESSION['redirect_after_login'] ?? $fallback);
    unset($_SESSION['redirect_after_login']);

    if ($target === '' || str_contains($target, '..') || str_starts_with($target, '/')) {
        return $fallback;
    }

    return $target;
}
