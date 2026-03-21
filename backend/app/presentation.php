<?php

declare(strict_types=1);

// Labels fuer Zutatenkategorien in der UI.
function ingredient_category_labels(): array
{
    return ingredient_category_label_map();
}

// Einheitliche Labels fuer Konfigurationswerte (z. B. Dashboard, Summary, JS).
function configuration_option_labels(): array
{
    return configuration_adjustment_label_map();
}
