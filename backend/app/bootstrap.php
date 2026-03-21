<?php

declare(strict_types=1);

// Liest eine Umgebungsvariable mit Fallback-Wert.
function app_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

date_default_timezone_set(app_env('APP_TIMEZONE', 'Europe/Berlin'));

function app_is_https(): bool
{
    $https = $_SERVER['HTTPS'] ?? '';
    if (is_string($https) && strtolower($https) === 'on') {
        return true;
    }

    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    return is_string($forwardedProto) && strtolower($forwardedProto) === 'https';
}

// Basis-Sicherheitsheader für jede HTTP-Antwort.
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// Zentrale Session-Startlogik mit sicheren Cookie-Einstellungen.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => app_is_https(),
        'cookie_samesite' => 'Lax',
        'use_only_cookies' => true,
        'use_strict_mode' => true,
    ]);
}

$debugEnabled = app_env('APP_DEBUG', '0') === '1';
ini_set('display_errors', $debugEnabled ? '1' : '0');
error_reporting($debugEnabled ? E_ALL : 0);

function e(?string $value): string
{
    // Standard-HTML-Escaping für sichere Ausgabe in Templates.
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post_request(): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
}

function redirect(string $relativePath): void
{
    header('Location: ' . $relativePath);
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = (string) $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function read_json_input(): array
{
    // JSON-Body tolerant lesen: bei leerem/ungültigem Input leeres Array.
    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

// Gemeinsame App-Module laden (Reihenfolge ist bewusst zentralisiert).
require_once __DIR__ . '/../database/connector.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/pricing.php';
require_once __DIR__ . '/repositories.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/presentation.php';
require_once __DIR__ . '/services.php';
