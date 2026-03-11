<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/app/bootstrap.php';

require_login(true);

if (!is_post_request()) {
    json_response([
        'success' => false,
        'message' => 'Nur POST erlaubt.',
    ], 405);
}

$payload = read_json_input(); # liest die JSON-Daten aus dem Request-Body und dekodiert sie in ein assoziatives Array. Wenn die Daten ungültiges JSON sind, wird eine Fehlermeldung zurückgegeben.
csrf_validate_or_fail(csrf_token_from_request($payload), true); # überprüft, ob der CSRF-Token im Request gültig ist. Der Token wird entweder aus dem JSON-Payload oder aus den HTTP-Headern extrahiert. Wenn der Token ungültig oder nicht vorhanden ist, wird eine Fehlermeldung zurückgegeben.

$user = current_user();
if (!$user) {
    json_response([
        'success' => false,
        'message' => 'Anmeldung erforderlich.',
        'redirect_url' => 'login.php',
    ], 401);
}

$sizeId = (int) ($payload['size_id'] ?? 0);
$ingredientIds = validate_id_list($payload['ingredient_ids'] ?? []);
$toppingIds = validate_id_list($payload['topping_ids'] ?? []);

if ($sizeId <= 0) {
    json_response([
        'success' => false,
        'message' => 'Bitte eine Größe auswählen.',
    ], 422);
}

if ($ingredientIds === []) {
    json_response([
        'success' => false,
        'message' => 'Bitte mindestens eine Zutat auswählen.',
    ], 422);
}

$size = repo_get_size_by_id($sizeId);
if (!$size) {
    json_response([
        'success' => false,
        'message' => 'Ausgewählte Größe existiert nicht.',
    ], 422);
}

$ingredients = repo_get_ingredients_by_ids($ingredientIds);
if (count($ingredients) !== count($ingredientIds)) {
    json_response([
        'success' => false,
        'message' => 'Mindestens eine ausgewählte Zutat ist ungültig.',
    ], 422);
}

$toppings = repo_get_toppings_by_ids($toppingIds);
if (count($toppings) !== count($toppingIds)) {
    json_response([
        'success' => false,
        'message' => 'Mindestens ein ausgewähltes Topping ist ungültig.',
    ], 422);
}

$configurationName = validate_configuration_name($payload['name'] ?? 'Mein Smoothie');
$configurationName = clean_text_field($configurationName, 120);
if ($configurationName === 'Mein Smoothie') {
    $configurationName = 'Mein Smoothie ' . date('d.m.Y H:i');
}

# liest und validiert die Werte aus Schritt 3, z.B. Süße, Konsistenz, Temperatur und Süßungsmittel. Wenn ungültige Werte übergeben werden, werden Standardwerte verwendet.
$sweetness = validate_enum_value((string) ($payload['sweetness'] ?? 'medium'), allowed_sweetness_values(), 'medium'); # validate_enum_value ist eine Hilfsfunktion, die überprüft, ob der übergebene Wert in der Liste der erlaubten Werte enthalten ist. Wenn nicht, wird der Standardwert zurückgegeben.
$consistency = validate_enum_value((string) ($payload['consistency'] ?? 'standard'), allowed_consistency_values(), 'standard');
$temperature = validate_enum_value((string) ($payload['temperature'] ?? 'chilled'), allowed_temperature_values(), 'chilled');
$sweetenerType = validate_enum_value((string) ($payload['sweetener_type'] ?? 'none'), allowed_sweetener_type_values(), 'none');

$couponCode = strtoupper(clean_text_field($payload['coupon_code'] ?? '', 50));
$coupon = null;
if ($couponCode !== '') {
    $coupon = repo_find_coupon($couponCode);
    if (!$coupon) {
        json_response([
            'success' => false,
            'message' => 'Gutscheincode wurde nicht gefunden.',
        ], 422);
    }
}

$pricing = calculate_pricing($size, $ingredients, $toppings, $coupon);
if ($couponCode !== '' && !$pricing['coupon_valid']) {
    json_response([
        'success' => false,
        'message' => $pricing['coupon_message'] !== '' ? $pricing['coupon_message'] : 'Gutscheincode ist ungültig.',
    ], 422);
}

try {
    $configurationId = repo_save_configuration(
        (int) $user['id'],
        [
            'name' => $configurationName,
            'size_id' => $sizeId,
            'sweetness' => $sweetness,
            'consistency' => $consistency,
            'temperature' => $temperature,
            'sweetener_type' => $sweetenerType,
        ],
        $ingredientIds,
        $toppingIds,
        (float) $pricing['subtotal'],
        (float) $pricing['discount_amount'],
        (float) $pricing['total_price'],
        ($couponCode !== '' && $pricing['coupon_valid']) ? $couponCode : null
    );
} catch (Throwable $throwable) {
    json_response([
        'success' => false,
        'message' => 'Konfiguration konnte nicht gespeichert werden.',
        'debug' => app_env('APP_DEBUG', '1') === '1' ? $throwable->getMessage() : null,
    ], 500);
}

json_response([
    'success' => true,
    'configuration_id' => $configurationId,
    'redirect_url' => 'dashboard.php',
    'message' => 'Konfiguration wurde gespeichert.',
    'pricing' => [
        'subtotal' => $pricing['subtotal'],
        'discount_amount' => $pricing['discount_amount'],
        'total_price' => $pricing['total_price'],
    ],
]);
