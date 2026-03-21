<?php

declare(strict_types=1);

// Labels fuer Zutatenkategorien in der UI.
function ingredient_category_labels(): array
{
    return [
        'fruit' => 'Obst',
        'vegetable' => 'Gemüse',
        'protein' => 'Protein',
    ];
}

// Einheitliche Labels fuer Konfigurationswerte (z. B. Dashboard, Summary, JS).
function configuration_option_labels(): array
{
    return [
        'sweetness' => [
            'none' => 'Kein Zucker',
            'low' => 'Wenig',
            'medium' => 'Mittel',
            'high' => 'Süß',
        ],
        'consistency' => [
            'liquid' => 'Flüssig',
            'standard' => 'Standard',
            'creamy' => 'Cremig',
            'extra_creamy' => 'Extra cremig',
        ],
        'temperature' => [
            'chilled' => 'Gekühlt',
            'extra_cold' => 'Extra kalt',
            'frozen' => 'Frozen',
        ],
        'sweetener_type' => [
            'none' => 'ohne',
            'honey' => 'Honig',
            'agave' => 'Agave',
        ],
    ];
}

// Frontend-JS arbeitet bei sweetenerType mit camelCase.
function configuration_option_labels_for_js(): array
{
    $labels = configuration_option_labels();

    return [
        'sweetness' => $labels['sweetness'],
        'consistency' => $labels['consistency'],
        'temperature' => $labels['temperature'],
        'sweetenerType' => $labels['sweetener_type'],
    ];
}
