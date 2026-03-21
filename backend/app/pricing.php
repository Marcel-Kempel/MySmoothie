<?php

declare(strict_types=1);

// Erlaubte Auswahlwerte aus Schritt 3 (Frontend + Backend bleiben konsistent).
function allowed_sweetness_values(): array
{
    return ['none', 'low', 'medium', 'high'];
}

function allowed_consistency_values(): array
{
    return ['liquid', 'standard', 'creamy', 'extra_creamy'];
}

function allowed_temperature_values(): array
{
    return ['chilled', 'extra_cold', 'frozen'];
}

function allowed_sweetener_type_values(): array
{
    return ['none', 'honey', 'agave'];
}

// Prüft Aktivstatus und optionales Ablaufdatum eines Gutscheins.
function is_coupon_currently_valid(?array $coupon): bool
{
    if (!is_array($coupon)) {
        return false;
    }

    if ((int) ($coupon['is_active'] ?? 0) !== 1) {
        return false;
    }

    $validUntil = $coupon['valid_until'] ?? null;
    if ($validUntil === null || $validUntil === '') {
        return true;
    }

    $today = date('Y-m-d');
    return $validUntil >= $today;
}

// Zentrale Preisberechnung inkl. optionaler Gutscheinlogik.
function calculate_pricing(array $size, array $ingredients, array $toppings, ?array $coupon): array
{
    $basePrice = (float) ($size['base_price'] ?? 0);
    $ingredientsPrice = 0.0;
    foreach ($ingredients as $ingredient) {
        $ingredientsPrice += (float) ($ingredient['price'] ?? 0);
    }

    $toppingsPrice = 0.0;
    foreach ($toppings as $topping) {
        $toppingsPrice += (float) ($topping['price'] ?? 0);
    }

    $subtotal = round($basePrice + $ingredientsPrice + $toppingsPrice, 2);

    $discountAmount = 0.0;
    $couponValid = false;
    $couponMessage = '';

    if (is_array($coupon)) {
        if (!is_coupon_currently_valid($coupon)) {
            $couponMessage = 'Gutscheincode ist nicht aktiv oder abgelaufen.';
        } else {
            $couponType = (string) ($coupon['discount_type'] ?? '');
            $couponValue = (float) ($coupon['discount_value'] ?? 0);

            if ($couponType === 'percent') {
                $discountAmount = round($subtotal * ($couponValue / 100), 2);
                $couponValid = true;
            } elseif ($couponType === 'fixed') {
                $discountAmount = round($couponValue, 2);
                $couponValid = true;
            } else {
                $couponMessage = 'Gutscheincode konnte nicht verarbeitet werden.';
            }
        }
    }

    if ($discountAmount > $subtotal) {
        $discountAmount = $subtotal;
    }

    $totalPrice = round($subtotal - $discountAmount, 2);

    return [
        'base_price' => round($basePrice, 2),
        'ingredients_price' => round($ingredientsPrice, 2),
        'toppings_price' => round($toppingsPrice, 2),
        'subtotal' => $subtotal,
        'discount_amount' => round($discountAmount, 2),
        'total_price' => $totalPrice,
        'coupon_valid' => $couponValid,
        'coupon_message' => $couponMessage,
    ];
}
