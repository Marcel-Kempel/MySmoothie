<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/app/bootstrap.php';

// API-Endpunkt ist nur für eingeloggte Nutzer gedacht.
require_login(true);

if (!is_post_request()) {
    json_response([
        'success' => false,
        'message' => 'Nur POST erlaubt.',
    ], 405);
}

// 1) Request einlesen und Sicherheitsprüfung durchführen.
$payload = read_json_input();
csrf_validate_or_fail(csrf_token_from_request($payload), true);

$user = current_user();
// Defensive Absicherung für den Fall eines inkonsistenten Session-Zustands.
if (!is_array($user)) {
    json_response([
        'success' => false,
        'message' => 'Anmeldung erforderlich.',
        'redirect_url' => 'login.php',
    ], 401);
}

// 2) Fachlogik komplett ueber den Backend-Service abbilden.
$result = service_save_configuration_for_user((int) $user['id'], $payload);
json_response($result['payload'], (int) $result['status']);
