<?php

declare(strict_types=1);

// -------------------- User --------------------
function repo_find_user_by_email(string $email): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute([':email' => $email]);
    $user = $statement->fetch();

    return is_array($user) ? $user : null;
}

function repo_find_user_by_id(int $id): ?array
{
    $statement = db()->prepare('SELECT id, first_name, last_name, address, email, created_at FROM users WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $id]);
    $user = $statement->fetch();

    return is_array($user) ? $user : null;
}

function repo_create_user(array $values): int
{
    $statement = db()->prepare(
        'INSERT INTO users (first_name, last_name, address, email, password_hash) VALUES (:first_name, :last_name, :address, :email, :password_hash)'
    );

    $statement->execute([
        ':first_name' => $values['first_name'],
        ':last_name' => $values['last_name'],
        ':address' => $values['address'],
        ':email' => $values['email'],
        ':password_hash' => $values['password_hash'],
    ]);

    return (int) db()->lastInsertId();
}

// -------------------- Konfigurator-Stammdaten --------------------
// Baut eine SELECT-Spaltenliste aus einem zentralen Column-Array.
function repo_select_sql_from_columns(array $columns, string $tableAlias = ''): string
{
    $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';
    $parts = [];

    foreach ($columns as $column) {
        $column = is_scalar($column) ? trim((string) $column) : '';
        if ($column === '') {
            continue;
        }

        $parts[] = $prefix . $column;
    }

    return implode(', ', $parts);
}

function repo_get_sizes(): array
{
    $columns = configurator_selection_columns('sizes');
    $selectSql = repo_select_sql_from_columns($columns);
    if ($selectSql === '') {
        $selectSql = 'id, name, ml, base_price';
    }

    $orderSql = configurator_selection_order_sql('sizes');
    if ($orderSql === '') {
        $orderSql = 'ml ASC';
    }

    $statement = db()->query('SELECT ' . $selectSql . ' FROM sizes ORDER BY ' . $orderSql);
    return $statement->fetchAll();
}

function repo_get_size_by_id(int $sizeId): ?array
{
    $columns = configurator_selection_columns('sizes');
    $selectSql = repo_select_sql_from_columns($columns);
    if ($selectSql === '') {
        $selectSql = 'id, name, ml, base_price';
    }

    $statement = db()->prepare('SELECT ' . $selectSql . ' FROM sizes WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $sizeId]);
    $size = $statement->fetch();

    return is_array($size) ? $size : null;
}

function repo_ingredient_select_sql(string $tableAlias = ''): string
{
    $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';
    $columns = array_merge(
        ['id', 'name', 'category', 'price', 'image_url'],
        ingredient_feature_columns()
    );

    $parts = [];
    foreach ($columns as $column) {
        $parts[] = $prefix . $column;
    }

    return implode(', ', $parts);
}

function repo_get_ingredients(): array
{
    $ingredientSelectSql = repo_ingredient_select_sql();
    $statement = db()->query(
        'SELECT ' . $ingredientSelectSql . ' FROM ingredients WHERE is_active = 1 ORDER BY category, name'
    );

    return $statement->fetchAll();
}

function repo_get_toppings(): array
{
    $columns = configurator_selection_columns('toppings');
    $selectSql = repo_select_sql_from_columns($columns);
    if ($selectSql === '') {
        $selectSql = 'id, name, price';
    }

    $orderSql = configurator_selection_order_sql('toppings');
    if ($orderSql === '') {
        $orderSql = 'name ASC';
    }

    $statement = db()->query('SELECT ' . $selectSql . ' FROM toppings ORDER BY ' . $orderSql);
    return $statement->fetchAll();
}

// Zentrale Liste der Anpassungsspalten (Step 3), damit SQL nicht mehrfach gepflegt wird.
function repo_configuration_adjustment_columns(): array
{
    return array_keys(configuration_adjustment_fields());
}

function repo_adjustment_select_sql(string $tableAlias): string
{
    $parts = [];
    foreach (repo_configuration_adjustment_columns() as $column) {
        $parts[] = $tableAlias . '.' . $column;
    }

    return implode(",\n            ", $parts);
}

// -------------------- ID-Listen für IN-Klauseln --------------------
function repo_build_in_clause(array $ids, string $prefix): array
{
    // Baut dynamische Platzhalter für IN(...) ohne String-Interpolation von User-Daten.
    $placeholders = [];
    $params = [];

    foreach (array_values($ids) as $index => $id) {
        $key = ':' . $prefix . $index;
        $placeholders[] = $key;
        $params[$key] = (int) $id;
    }

    return [
        'sql' => implode(', ', $placeholders),
        'params' => $params,
    ];
}

// Lädt genau die Zutaten, die per ID ausgewählt wurden.
function repo_get_ingredients_by_ids(array $ingredientIds): array
{
    if ($ingredientIds === []) {
        return [];
    }

    $ingredientSelectSql = repo_ingredient_select_sql();
    $clause = repo_build_in_clause($ingredientIds, 'ingredient_id_');
    $statement = db()->prepare(
        'SELECT ' . $ingredientSelectSql . ' FROM ingredients WHERE is_active = 1 AND id IN (' . $clause['sql'] . ')'
    );
    $statement->execute($clause['params']);

    return $statement->fetchAll();
}

// Lädt genau die Toppings, die per ID ausgewählt wurden.
function repo_get_toppings_by_ids(array $toppingIds): array
{
    if ($toppingIds === []) {
        return [];
    }

    $clause = repo_build_in_clause($toppingIds, 'topping_id_');
    $columns = configurator_selection_columns('toppings');
    $selectSql = repo_select_sql_from_columns($columns);
    if ($selectSql === '') {
        $selectSql = 'id, name, price';
    }

    $statement = db()->prepare(
        'SELECT ' . $selectSql . ' FROM toppings WHERE id IN (' . $clause['sql'] . ')'
    );
    $statement->execute($clause['params']);

    return $statement->fetchAll();
}

// -------------------- Presets und Gutscheine --------------------
function repo_get_presets(): array
{
    $presetColumns = configurator_selection_columns('presets');
    $presetSelectSql = repo_select_sql_from_columns($presetColumns, 'p');
    if ($presetSelectSql === '') {
        $presetSelectSql = 'p.id, p.name, p.description, p.size_id';
    }

    $presetOrderSql = configurator_selection_order_sql('presets', 'p');
    if ($presetOrderSql === '') {
        $presetOrderSql = 'p.name ASC';
    }

    $adjustmentColumns = repo_configuration_adjustment_columns();
    $adjustmentDefaults = configuration_adjustment_defaults();
    $adjustmentSelectSql = repo_adjustment_select_sql('p');
    $adjustmentSelectFragment = $adjustmentSelectSql !== '' ? "            {$adjustmentSelectSql},\n" : '';

    // Presets inkl. Zutatenliste für schnelle Anzeige im Konfigurator laden.
    $statement = db()->query(
        "SELECT
            {$presetSelectSql},
{$adjustmentSelectFragment}            s.name AS size_name,
            s.ml AS size_ml,
            s.base_price,
            GROUP_CONCAT(pi.ingredient_id ORDER BY pi.ingredient_id) AS ingredient_ids,
            GROUP_CONCAT(i.name ORDER BY pi.ingredient_id SEPARATOR ', ') AS ingredient_names
         FROM presets p
         INNER JOIN sizes s ON s.id = p.size_id
         LEFT JOIN preset_ingredients pi ON pi.preset_id = p.id
         LEFT JOIN ingredients i ON i.id = pi.ingredient_id
         GROUP BY p.id
         ORDER BY {$presetOrderSql}"
    );

    $rows = $statement->fetchAll();
    $presets = [];

    foreach ($rows as $row) {
        $ingredientIds = [];
        if (!empty($row['ingredient_ids'])) {
            $ingredientIds = array_map('intval', explode(',', (string) $row['ingredient_ids']));
        }

        $preset = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'description' => (string) $row['description'],
            'size_id' => (int) $row['size_id'],
            'size_name' => (string) $row['size_name'],
            'size_ml' => (int) $row['size_ml'],
            'base_price' => (float) $row['base_price'],
            'ingredient_ids' => $ingredientIds,
            'ingredient_names' => (string) ($row['ingredient_names'] ?? ''),
        ];

        foreach ($adjustmentColumns as $column) {
            $default = $adjustmentDefaults[$column] ?? '';
            $preset[$column] = (string) ($row[$column] ?? $default);
        }

        $presets[] = $preset;
    }

    return $presets;
}

function repo_find_coupon(string $couponCode): ?array
{
    $statement = db()->prepare('SELECT code, discount_type, discount_value, is_active, valid_until FROM coupons WHERE code = :code LIMIT 1');
    $statement->execute([':code' => strtoupper($couponCode)]);
    $coupon = $statement->fetch();

    return is_array($coupon) ? $coupon : null;
}

// -------------------- Persistenz von User-Konfigurationen --------------------
function repo_save_configuration(
    int $userId,
    array $configuration,
    array $ingredientIds,
    array $toppingIds,
    float $subtotal,
    float $discountAmount,
    float $totalPrice,
    ?string $couponCode
): int {
    $pdo = db();

    try {
        // Transaktion garantiert: entweder alles (Kopf + Relationen) oder nichts.
        $pdo->beginTransaction();

        $adjustmentColumns = repo_configuration_adjustment_columns();
        $adjustmentDefaults = configuration_adjustment_defaults();

        $columns = ['user_id', 'name', 'size_id'];
        $params = [
            ':user_id' => $userId,
            ':name' => (string) ($configuration['name'] ?? ''),
            ':size_id' => (int) ($configuration['size_id'] ?? 0),
        ];

        foreach ($adjustmentColumns as $column) {
            $columns[] = $column;
            $params[':' . $column] = (string) ($configuration[$column] ?? ($adjustmentDefaults[$column] ?? ''));
        }

        $columns = array_merge($columns, ['subtotal', 'discount_amount', 'total_price', 'coupon_code']);
        $params[':subtotal'] = $subtotal;
        $params[':discount_amount'] = $discountAmount;
        $params[':total_price'] = $totalPrice;
        $params[':coupon_code'] = $couponCode;

        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $insertConfiguration = $pdo->prepare(
            'INSERT INTO configurations (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $placeholders) . ')'
        );

        $insertConfiguration->execute($params);

        $configurationId = (int) $pdo->lastInsertId();

        $insertIngredient = $pdo->prepare(
            'INSERT INTO configuration_ingredients (configuration_id, ingredient_id) VALUES (:configuration_id, :ingredient_id)'
        );

        foreach ($ingredientIds as $ingredientId) {
            $insertIngredient->execute([
                ':configuration_id' => $configurationId,
                ':ingredient_id' => (int) $ingredientId,
            ]);
        }

        if ($toppingIds !== []) {
            $insertTopping = $pdo->prepare(
                'INSERT INTO configuration_toppings (configuration_id, topping_id) VALUES (:configuration_id, :topping_id)'
            );

            foreach ($toppingIds as $toppingId) {
                $insertTopping->execute([
                    ':configuration_id' => $configurationId,
                    ':topping_id' => (int) $toppingId,
                ]);
            }
        }

        $pdo->commit();
        return $configurationId;
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $throwable;
    }
}

function repo_get_user_configurations(int $userId): array
{
    $adjustmentSelectSql = repo_adjustment_select_sql('c');
    $adjustmentSelectFragment = $adjustmentSelectSql !== '' ? "            {$adjustmentSelectSql},\n" : '';

    // Erst Kopfdatensätze laden, danach Zutaten/Toppings je Konfiguration anreichern.
    $configurationStatement = db()->prepare(
        "SELECT
            c.id,
            c.name,
{$adjustmentSelectFragment}            c.subtotal,
            c.discount_amount,
            c.total_price,
            c.coupon_code,
            c.created_at,
            s.name AS size_name,
            s.ml AS size_ml,
            s.base_price AS size_base_price
         FROM configurations c
         INNER JOIN sizes s ON s.id = c.size_id
         WHERE c.user_id = :user_id
         ORDER BY c.created_at DESC"
    );

    $configurationStatement->execute([':user_id' => $userId]);
    $configurations = $configurationStatement->fetchAll();

    $ingredientsStatement = db()->prepare(
        'SELECT i.id, i.name, i.category, i.price
         FROM configuration_ingredients ci
         INNER JOIN ingredients i ON i.id = ci.ingredient_id
         WHERE ci.configuration_id = :configuration_id
         ORDER BY i.name'
    );

    $toppingsStatement = db()->prepare(
        'SELECT t.id, t.name, t.price
         FROM configuration_toppings ct
         INNER JOIN toppings t ON t.id = ct.topping_id
         WHERE ct.configuration_id = :configuration_id
         ORDER BY t.name'
    );

    foreach ($configurations as &$configuration) {
        $ingredientsStatement->execute([':configuration_id' => $configuration['id']]);
        $configuration['ingredients'] = $ingredientsStatement->fetchAll();

        $toppingsStatement->execute([':configuration_id' => $configuration['id']]);
        $configuration['toppings'] = $toppingsStatement->fetchAll();
    }
    unset($configuration);

    return $configurations;
}

// Löscht eine Konfiguration ausschließlich im Besitz des angegebenen Users.
function repo_delete_configuration_for_user(int $configurationId, int $userId): bool
{
    $statement = db()->prepare('DELETE FROM configurations WHERE id = :id AND user_id = :user_id');
    $statement->execute([
        ':id' => $configurationId,
        ':user_id' => $userId,
    ]);

    return $statement->rowCount() > 0;
}
