<?php

declare(strict_types=1);

// -------------------- Daten fuer Seiten --------------------
function service_get_configurator_catalog(): array
{
    return [
        'sizes' => repo_get_sizes(),
        'ingredients' => repo_get_ingredients(),
        'toppings' => repo_get_toppings(),
        'presets' => repo_get_presets(),
    ];
}

function service_get_dashboard_data(int $userId): array
{
    return [
        'configurations' => repo_get_user_configurations($userId),
    ];
}

function service_delete_user_configuration(int $userId, int $configurationId): bool
{
    if ($configurationId <= 0) {
        return false;
    }

    return repo_delete_configuration_for_user($configurationId, $userId);
}

// -------------------- Auth-Use-Cases --------------------
function service_register_user(array $input): array
{
    $validated = validate_registration_input($input);
    $values = $validated['values'];
    $errors = $validated['errors'];

    if ($errors === [] && repo_find_user_by_email($values['email']) !== null) {
        $errors['email'] = 'Diese E-Mail-Adresse ist bereits registriert.';
    }

    if ($errors !== []) {
        return [
            'success' => false,
            'values' => $values,
            'errors' => $errors,
            'user_id' => null,
        ];
    }

    $newUserId = repo_create_user([
        'first_name' => $values['first_name'],
        'last_name' => $values['last_name'],
        'address' => $values['address'],
        'email' => $values['email'],
        'password_hash' => hash_user_password((string) $validated['password']),
    ]);

    return [
        'success' => true,
        'values' => $values,
        'errors' => [],
        'user_id' => $newUserId,
    ];
}

function service_authenticate_user(array $input): array
{
    $validated = validate_login_input($input);
    $values = $validated['values'];
    $errors = $validated['errors'];

    if ($errors !== []) {
        return [
            'success' => false,
            'values' => $values,
            'errors' => $errors,
            'user' => null,
            'general_error' => '',
        ];
    }

    $user = repo_find_user_by_email($values['email']);
    if (is_array($user) && verify_user_password((string) $validated['password'], (string) $user['password_hash'])) {
        return [
            'success' => true,
            'values' => $values,
            'errors' => [],
            'user' => $user,
            'general_error' => '',
        ];
    }

    return [
        'success' => false,
        'values' => $values,
        'errors' => [],
        'user' => null,
        'general_error' => 'E-Mail oder Passwort ist ungültig.',
    ];
}

// -------------------- Konfigurator-Use-Cases --------------------
function service_parse_selection_from_payload(array $payload): array
{
    return [
        'size_id' => (int) ($payload['size_id'] ?? 0),
        'ingredient_ids' => validate_id_list($payload['ingredient_ids'] ?? []),
        'topping_ids' => validate_id_list($payload['topping_ids'] ?? []),
    ];
}

function service_resolve_selection(array $selection): array
{
    $sizeId = (int) ($selection['size_id'] ?? 0);
    $ingredientIds = $selection['ingredient_ids'] ?? [];
    $toppingIds = $selection['topping_ids'] ?? [];

    if ($sizeId <= 0) {
        return [
            'ok' => false,
            'status' => 422,
            'message' => 'Bitte eine Größe auswählen.',
        ];
    }

    if (!is_array($ingredientIds) || $ingredientIds === []) {
        return [
            'ok' => false,
            'status' => 422,
            'message' => 'Bitte mindestens eine Zutat auswählen.',
        ];
    }

    $size = repo_get_size_by_id($sizeId);
    if (!is_array($size)) {
        return [
            'ok' => false,
            'status' => 422,
            'message' => 'Ausgewählte Größe existiert nicht.',
        ];
    }

    $ingredients = repo_get_ingredients_by_ids($ingredientIds);
    if (count($ingredients) !== count($ingredientIds)) {
        return [
            'ok' => false,
            'status' => 422,
            'message' => 'Mindestens eine ausgewählte Zutat ist ungültig.',
        ];
    }

    $toppings = repo_get_toppings_by_ids($toppingIds);
    if (count($toppings) !== count($toppingIds)) {
        return [
            'ok' => false,
            'status' => 422,
            'message' => 'Mindestens ein ausgewähltes Topping ist ungültig.',
        ];
    }

    return [
        'ok' => true,
        'size_id' => $sizeId,
        'ingredient_ids' => $ingredientIds,
        'topping_ids' => $toppingIds,
        'size' => $size,
        'ingredients' => $ingredients,
        'toppings' => $toppings,
    ];
}

function service_normalize_coupon_code(mixed $raw): string
{
    return strtoupper(clean_text_field($raw ?? '', 50));
}

function service_preview_coupon_pricing(array $payload): array
{
    $selection = service_resolve_selection(service_parse_selection_from_payload($payload));
    if (!$selection['ok']) {
        return [
            'status' => (int) $selection['status'],
            'payload' => [
                'success' => false,
                'message' => $selection['message'],
            ],
        ];
    }

    $couponCode = service_normalize_coupon_code($payload['coupon_code'] ?? '');
    $coupon = null;
    if ($couponCode !== '') {
        $coupon = repo_find_coupon($couponCode);
    }

    $pricing = calculate_pricing(
        $selection['size'],
        $selection['ingredients'],
        $selection['toppings'],
        $coupon
    );

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

    return [
        'status' => 200,
        'payload' => [
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
        ],
    ];
}

function service_save_configuration_for_user(int $userId, array $payload): array
{
    $selection = service_resolve_selection(service_parse_selection_from_payload($payload));
    if (!$selection['ok']) {
        return [
            'status' => (int) $selection['status'],
            'payload' => [
                'success' => false,
                'message' => $selection['message'],
            ],
        ];
    }

    $configurationName = validate_configuration_name($payload['name'] ?? 'Mein Smoothie');
    $adjustments = configuration_adjustment_values_from_payload($payload);

    $couponCode = service_normalize_coupon_code($payload['coupon_code'] ?? '');
    $coupon = null;

    if ($couponCode !== '') {
        $coupon = repo_find_coupon($couponCode);
        if (!is_array($coupon)) {
            return [
                'status' => 422,
                'payload' => [
                    'success' => false,
                    'message' => 'Gutscheincode wurde nicht gefunden.',
                ],
            ];
        }
    }

    $pricing = calculate_pricing(
        $selection['size'],
        $selection['ingredients'],
        $selection['toppings'],
        $coupon
    );

    if ($couponCode !== '' && !$pricing['coupon_valid']) {
        return [
            'status' => 422,
            'payload' => [
                'success' => false,
                'message' => $pricing['coupon_message'] !== '' ? $pricing['coupon_message'] : 'Gutscheincode ist ungültig.',
            ],
        ];
    }

    try {
        $configurationId = repo_save_configuration(
            $userId,
            [
                'name' => $configurationName,
                'size_id' => $selection['size_id'],
                ...$adjustments,
            ],
            $selection['ingredient_ids'],
            $selection['topping_ids'],
            (float) $pricing['subtotal'],
            (float) $pricing['discount_amount'],
            (float) $pricing['total_price'],
            ($couponCode !== '' && $pricing['coupon_valid']) ? $couponCode : null
        );
    } catch (Throwable $throwable) {
        return [
            'status' => 500,
            'payload' => [
                'success' => false,
                'message' => 'Konfiguration konnte nicht gespeichert werden.',
                'debug' => app_env('APP_DEBUG', '0') === '1' ? $throwable->getMessage() : null,
            ],
        ];
    }

    return [
        'status' => 200,
        'payload' => [
            'success' => true,
            'configuration_id' => $configurationId,
            'redirect_url' => 'dashboard.php',
            'message' => 'Konfiguration wurde gespeichert.',
            'pricing' => [
                'subtotal' => $pricing['subtotal'],
                'discount_amount' => $pricing['discount_amount'],
                'total_price' => $pricing['total_price'],
            ],
        ],
    ];
}
