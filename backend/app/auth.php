<?php

declare(strict_types=1);

// Optionaler Pepper fuer Passwort-Hashing (ueber .env konfigurierbar).
function password_pepper(): string
{
    return app_env('PASSWORD_PEPPER', '');
}

function password_material(string $plainPassword): string
{
    $pepper = password_pepper();
    if ($pepper === '') {
        return $plainPassword;
    }

    return hash_hmac('sha256', $plainPassword, $pepper);
}

// Zentrale Erzeugung des Passwort-Hashes fuer Registrierungen.
function hash_user_password(string $plainPassword): string
{
    return password_hash(password_material($plainPassword), PASSWORD_DEFAULT);
}

// Passwortpruefung inkl. Rueckwaertskompatibilitaet fuer alte Hashes ohne Pepper.
function verify_user_password(string $plainPassword, string $storedHash): bool
{
    if (password_verify(password_material($plainPassword), $storedHash)) {
        return true;
    }

    if (password_pepper() !== '' && password_verify($plainPassword, $storedHash)) {
        return true;
    }

    return false;
}

// Setzt die User-ID in die Session und erneuert die Session-ID gegen Fixation.
function login_user_by_id(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

// Entfernt Login-Zustand und erneuert die Session-ID.
function logout_user(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

// Liefert den aktuell eingeloggten User (mit kleinem In-Request-Cache).
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

// Kurzprüfung für geschützte Bereiche.
function is_logged_in(): bool
{
    return is_array(current_user());
}

// Erzwingt Login; bei API-Calls optional als JSON-401 statt Redirect.
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

// Liest das nach Login gewünschte Ziel und verhindert unsichere Redirects.
function consume_post_login_redirect(string $fallback = 'dashboard.php'): string
{
    $target = (string) ($_SESSION['redirect_after_login'] ?? $fallback);
    unset($_SESSION['redirect_after_login']);

    if ($target === '' || str_contains($target, '..') || str_starts_with($target, '/')) {
        return $fallback;
    }

    return $target;
}
