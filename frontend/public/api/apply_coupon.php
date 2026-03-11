<!-- diese Datei wird von JavaScript aufgerufen, um die Gültigkeit eines Gutscheincodes zu überprüfen und 
den Preis zu berechnen. Sie erwartet einen POST-Request mit JSON-Daten, die die ausgewählte Größe, Zutaten,
Toppings und den Gutscheincode enthalten. -->
<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/app/bootstrap.php';

if (!is_post_request()) {
    json_response([
        'success' => false,
        'message' => 'Nur POST erlaubt.',
    ], 405);
}

$payload = read_json_input();
csrf_validate_or_fail(csrf_token_from_request($payload), true);

$sizeId = (int) ($payload['size_id'] ?? 0);
$ingredientIds = validate_id_list($payload['ingredient_ids'] ?? []);
$toppingIds = validate_id_list($payload['topping_ids'] ?? []);
$couponCode = strtoupper(clean_text_field($payload['coupon_code'] ?? '', 50));

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

$coupon = null;
if ($couponCode !== '') {
    $coupon = repo_find_coupon($couponCode);
}

$pricing = calculate_pricing($size, $ingredients, $toppings, $coupon);

$message = 'Preis erfolgreich berechnet.';
$valid = false;

if ($couponCode === '') {
    $message = 'Kein Gutscheincode eingegeben.';
} elseif ($coupon === null) {
    $message = 'Gutscheincode wurde nicht gefunden.';
} elseif (!$pricing['coupon_valid']) {
    $message = $pricing['coupon_message'] !== '' ? $pricing['coupon_message'] : 'Gutscheincode ist ungültig.';
} else {
    $valid = true;
    $message = 'Gutscheincode wurde angewendet.';
}

json_response([
    'success' => true,
    'valid' => $valid,
    'coupon_code' => $couponCode,
    'message' => $message,
    'pricing' => [
        'base_price' => $pricing['base_price'],
        'ingredients_price' => $pricing['ingredients_price'],
        'toppings_price' => $pricing['toppings_price'],
        'subtotal' => $pricing['subtotal'],
        'discount_amount' => $pricing['discount_amount'],
        'total_price' => $pricing['total_price'],
    ],
]);
