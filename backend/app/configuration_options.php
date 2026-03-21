<?php

declare(strict_types=1);

// Zentrale Definition fuer alle Step-3-Anpassungen.
// Neue Dropdowns werden hier einmalig beschrieben und von UI + Backend genutzt.
function configuration_adjustment_fields(): array
{
    return [
        'sweetness' => [
            'label' => 'Süßgrad',
            'default' => 'medium',
            'js_key' => 'sweetness',
            'options' => [
                'none' => 'Kein Zucker',
                'low' => 'Wenig',
                'medium' => 'Mittel',
                'high' => 'Süß',
            ],
        ],
        'consistency' => [
            'label' => 'Konsistenz',
            'default' => 'standard',
            'js_key' => 'consistency',
            'options' => [
                'liquid' => 'Flüssig',
                'standard' => 'Standard',
                'creamy' => 'Cremig',
                'extra_creamy' => 'Extra cremig',
            ],
        ],
        'temperature' => [
            'label' => 'Temperatur',
            'default' => 'chilled',
            'js_key' => 'temperature',
            'options' => [
                'chilled' => 'Gekühlt',
                'extra_cold' => 'Extra kalt',
                'frozen' => 'Frozen',
            ],
        ],
        'sweetener_type' => [
            'label' => 'Süßungsmittel',
            'default' => 'none',
            'js_key' => 'sweetenerType',
            'options' => [
                'none' => 'ohne',
                'honey' => 'Honig',
                'agave' => 'Agave',
            ],
        ],
    ];
}

function configuration_adjustment_defaults(): array
{
    $defaults = [];
    foreach (configuration_adjustment_fields() as $field => $definition) {
        $defaults[(string) $field] = (string) ($definition['default'] ?? '');
    }

    return $defaults;
}

function configuration_adjustment_allowed_values(string $field): array
{
    $definitions = configuration_adjustment_fields();
    $definition = $definitions[$field] ?? null;
    if (!is_array($definition)) {
        return [];
    }

    $options = $definition['options'] ?? null;
    if (!is_array($options)) {
        return [];
    }

    return array_keys($options);
}

// Labels im Backend-Schluessel (snake_case), z. B. fuer Dashboard.
function configuration_adjustment_label_map(): array
{
    $labelMap = [];
    foreach (configuration_adjustment_fields() as $field => $definition) {
        $options = $definition['options'] ?? [];
        if (!is_array($options)) {
            continue;
        }

        $labelMap[(string) $field] = $options;
    }

    return $labelMap;
}

// UI-Metadaten fuer dynamisches Rendern in PHP/JS.
function configuration_adjustment_ui_definitions(): array
{
    $definitions = [];
    foreach (configuration_adjustment_fields() as $field => $definition) {
        $label = (string) ($definition['label'] ?? $field);
        $default = (string) ($definition['default'] ?? '');
        $jsKey = (string) ($definition['js_key'] ?? $field);

        $optionRows = [];
        $options = $definition['options'] ?? [];
        if (is_array($options)) {
            foreach ($options as $value => $optionLabel) {
                $optionRows[] = [
                    'value' => (string) $value,
                    'label' => (string) $optionLabel,
                ];
            }
        }

        $definitions[] = [
            'field' => (string) $field,
            'label' => $label,
            'default' => $default,
            'js_key' => $jsKey,
            'options' => $optionRows,
        ];
    }

    return $definitions;
}

// Liest Anpassungswerte robust aus Payload (legacy flat + neues adjustments-Objekt).
function configuration_adjustment_values_from_payload(array $payload): array
{
    $values = [];
    $defaults = configuration_adjustment_defaults();
    $definitions = configuration_adjustment_fields();
    $payloadAdjustments = isset($payload['adjustments']) && is_array($payload['adjustments'])
        ? $payload['adjustments']
        : [];

    foreach ($definitions as $field => $definition) {
        $field = (string) $field;
        $jsKey = (string) ($definition['js_key'] ?? $field);
        $default = $defaults[$field] ?? '';

        $rawValue = $payload[$field]
            ?? $payload[$jsKey]
            ?? $payloadAdjustments[$field]
            ?? $payloadAdjustments[$jsKey]
            ?? $default;

        $value = is_scalar($rawValue) ? trim((string) $rawValue) : $default;
        $allowed = configuration_adjustment_allowed_values($field);

        if (!in_array($value, $allowed, true)) {
            $value = $default;
        }

        $values[$field] = $value;
    }

    return $values;
}

// Zentrale Definition der Zutatenkategorien inkl. Anzeigefarbe (z. B. Visualisierung).
function ingredient_category_definitions(): array
{
    return [
        'fruit' => [
            'label' => 'Obst',
            'color' => '#ff8fa3',
        ],
        'vegetable' => [
            'label' => 'Gemüse',
            'color' => '#72c878',
        ],
        'protein' => [
            'label' => 'Protein',
            'color' => '#d2b48c',
        ],
    ];
}

function ingredient_category_label_map(): array
{
    $labels = [];
    foreach (ingredient_category_definitions() as $category => $definition) {
        $labels[(string) $category] = (string) ($definition['label'] ?? (string) $category);
    }

    return $labels;
}

function ingredient_category_color_map(): array
{
    $colors = [];
    foreach (ingredient_category_definitions() as $category => $definition) {
        $colors[(string) $category] = (string) ($definition['color'] ?? '#9ec5fe');
    }

    return $colors;
}

function ingredient_category_ui_definitions(): array
{
    $definitions = [];
    foreach (ingredient_category_definitions() as $category => $definition) {
        $definitions[] = [
            'value' => (string) $category,
            'label' => (string) ($definition['label'] ?? (string) $category),
            'color' => (string) ($definition['color'] ?? '#9ec5fe'),
        ];
    }

    return $definitions;
}

// Zentrale Badge-Definitionen fuer Zutatenkarten (Step 2).
function ingredient_feature_badge_definitions(): array
{
    return [
        'is_vegan' => 'Vegan',
        'is_lactose_free' => 'Laktosefrei',
        'is_high_protein' => 'High Protein',
        'is_low_sugar' => 'Low Sugar',
    ];
}

function ingredient_feature_columns(): array
{
    return array_keys(ingredient_feature_badge_definitions());
}

function ingredient_feature_badge_rows(): array
{
    $rows = [];
    foreach (ingredient_feature_badge_definitions() as $field => $label) {
        $rows[] = [
            'field' => (string) $field,
            'label' => (string) $label,
        ];
    }

    return $rows;
}
