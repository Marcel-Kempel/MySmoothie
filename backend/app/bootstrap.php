<?php

declare(strict_types=1);

# app_env() is a helper function to read environment variables with an optional default value.
# Umgebungsvariablen sind beispielweise in der docker-compose.yml.env definiert und können hiermit einfach abgerufen werden.
# liest Einstellungen, die außerhalb des Codes liegen, z.B. in der docker-compose.yml.env
# was steht in .env -> leiß was dort gilt, wenn nichts anderes definiert ist
function app_env(string $key, string $default = ''): string 
{
    $value = getenv($key); 
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

date_default_timezone_set(app_env('APP_TIMEZONE', 'Europe/Berlin'));

# Sichere Sitzungskonfiguration: HttpOnly-Cookies, SameSite-Attribut und strenger Modus
# PHP_SESSION_ACTIVE prüft, ob bereits eine Sitzung gestartet wurde, um Fehler zu vermeiden. Wenn keine Sitzung aktiv ist, wird eine neue Sitzung mit sicheren Einstellungen gestartet.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true, # Verhindert, dass JavaScript auf die Cookies zugreifen kann -> Schutz vor XSS-Angriffen
        'cookie_samesite' => 'Lax', # Verhindert, dass Cookies bei Cross-Site-Anfragen gesendet werden, außer bei Top-Level-Navigationen
        'use_strict_mode' => true, # Aktiviert den strengen Modus für die Sitzung
    ]);
}

$debugEnabled = app_env('APP_DEBUG', '1') === '1';
ini_set('display_errors', $debugEnabled ? '1' : '0');
error_reporting($debugEnabled ? E_ALL : 0);

function e(?string $value): string
{
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
# Alle Funktionen und Klassen in den folgenden Dateien werden hiermit eingebunden und stehen danach im gesamten Code zur Verfügung.
require_once __DIR__ . '/../database/connector.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/pricing.php';
require_once __DIR__ . '/repositories.php';
require_once __DIR__ . '/auth.php';