<?php

declare(strict_types=1);

// Erstellt (falls nötig) einen kryptografisch zufälligen CSRF-Token pro Session.
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Hilfsfunktion für klassische HTML-Formulare.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Vergleicht Request-Token timing-sicher gegen den Session-Token.
function csrf_validate_token(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? null;
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

// Unterstützt Token sowohl aus JSON-Payload als auch aus HTTP-Header.
function csrf_token_from_request(array $payload = []): string
{
    $fromPayload = $payload['csrf_token'] ?? null;
    if (is_string($fromPayload) && $fromPayload !== '') {
        return $fromPayload;
    }

    $fromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($fromHeader) ? trim($fromHeader) : '';
}

// Bricht bei ungültigem Token konsistent mit Fehlerantwort ab.
function csrf_validate_or_fail(?string $token, bool $asJson = false): void
{
    if (csrf_validate_token($token)) {
        return;
    }

    if ($asJson) {
        json_response([
            'success' => false,
            'message' => 'CSRF-Token ungültig.',
        ], 419);
    }

    flash_set('error', 'Sicherheitsprüfung fehlgeschlagen. Bitte erneut versuchen.');
    redirect('index.php');
}
