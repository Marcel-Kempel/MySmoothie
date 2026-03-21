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
        $fallback = $default;

        // Falls ein Default versehentlich nicht im Options-Set liegt, auf einen
        // sicheren erlaubten Wert zurückfallen statt einen DB-Fehler zu erzeugen.
        if (!in_array($fallback, $allowed, true)) {
            $fallback = $allowed[0] ?? '';
        }

        if (!in_array($value, $allowed, true)) {
            $value = $fallback;
        }

        $values[$field] = $value;
    }

    return $values;
}

// Zentrale Definitionen fuer DB-basierte Auswahlgruppen im Konfigurator.
// Neue Felder/Sortierung fuer Groessen, Toppings und Presets werden hier gepflegt.
function configurator_selection_definitions(): array
{
    return [
        'sizes' => [
            'title' => 'Größe wählen',
            'item_label_singular' => 'Größe',
            'item_label_plural' => 'Größen',
            'columns' => ['id', 'name', 'ml', 'base_price'],
            'order_by' => 'ml',
            'order_direction' => 'ASC',
        ],
        'toppings' => [
            'title' => 'Toppings',
            'item_label_singular' => 'Topping',
            'item_label_plural' => 'Toppings',
            'columns' => ['id', 'name', 'price'],
            'order_by' => 'name',
            'order_direction' => 'ASC',
        ],
        'presets' => [
            'title' => 'Preset laden',
            'item_label_singular' => 'Preset',
            'item_label_plural' => 'Presets',
            'columns' => ['id', 'name', 'description', 'size_id'],
            'order_by' => 'name',
            'order_direction' => 'ASC',
        ],
    ];
}

function configurator_selection_definition(string $selectionKey): array
{
    $definitions = configurator_selection_definitions();
    $definition = $definitions[$selectionKey] ?? null;

    return is_array($definition) ? $definition : [];
}

// Liefert sichere, SQL-taugliche Spaltennamen aus der zentralen Auswahldefinition.
function configurator_selection_columns(string $selectionKey): array
{
    $definition = configurator_selection_definition($selectionKey);
    $columns = $definition['columns'] ?? null;
    if (!is_array($columns)) {
        return [];
    }

    $safeColumns = [];
    foreach ($columns as $column) {
        $column = is_scalar($column) ? trim((string) $column) : '';
        if ($column === '') {
            continue;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            continue;
        }

        $safeColumns[] = $column;
    }

    return array_values(array_unique($safeColumns));
}

// Baut ein abgesichertes ORDER-BY-Fragment aus der zentralen Auswahldefinition.
function configurator_selection_order_sql(string $selectionKey, string $tableAlias = ''): string
{
    $definition = configurator_selection_definition($selectionKey);
    $orderBy = is_scalar($definition['order_by'] ?? null) ? trim((string) $definition['order_by']) : '';
    if ($orderBy === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $orderBy)) {
        return '';
    }

    $direction = strtoupper((string) ($definition['order_direction'] ?? 'ASC'));
    if ($direction !== 'DESC') {
        $direction = 'ASC';
    }

    $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';
    return $prefix . $orderBy . ' ' . $direction;
}

// UI-Metadaten (Titel/Bezeichnungen) fuer die Auswahlgruppen im Konfigurator.
function configurator_selection_ui_definitions(): array
{
    $ui = [];
    foreach (configurator_selection_definitions() as $key => $definition) {
        $selectionKey = (string) $key;
        $title = (string) ($definition['title'] ?? $selectionKey);
        $singular = (string) ($definition['item_label_singular'] ?? $selectionKey);
        $plural = (string) ($definition['item_label_plural'] ?? $singular . 's');

        $ui[$selectionKey] = [
            'key' => $selectionKey,
            'title' => $title,
            'item_label_singular' => $singular,
            'item_label_plural' => $plural,
        ];
    }

    return $ui;
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
