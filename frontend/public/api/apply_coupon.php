<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/app/bootstrap.php';

// Coupon-Check ist ausschließlich als POST-JSON-Endpoint vorgesehen.
if (!is_post_request()) {
    json_response([
        'success' => false,
        'message' => 'Nur POST erlaubt.',
    ], 405);
}

// 1) Request lesen und gegen CSRF absichern.
$payload = read_json_input();
csrf_validate_or_fail(csrf_token_from_request($payload), true);

// 2) Fachlogik im Backend-Service ausfuehren und Antwort unveraendert durchreichen.
$result = service_preview_coupon_pricing($payload);
json_response($result['payload'], (int) $result['status']);
