<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/app/bootstrap.php';

$sizes = repo_get_sizes();
$ingredients = repo_get_ingredients();
$toppings = repo_get_toppings();
$presets = repo_get_presets();

$categoryLabels = [
    'fruit' => 'Obst',
    'vegetable' => 'Gemüse',
    'protein' => 'Protein',
];

$configData = [
    'sizes' => $sizes,
    'ingredients' => $ingredients,
    'toppings' => $toppings,
    'presets' => $presets,
    'csrfToken' => csrf_token(),
    'isAuthenticated' => is_logged_in(),
    'api' => [
        'applyCoupon' => 'api/apply_coupon.php',
        'saveConfiguration' => 'api/save_configuration.php',
    ],
];

$pageTitle = 'MySmoothie | Konfigurator';
$activeNav = 'configurator';
$pageScripts = ['assets/js/configurator.js'];

include __DIR__ . '/../templates/layout/header.php';
?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h1 class="h4 mb-0">Smoothie Konfigurator</h1>
          <span class="badge text-bg-success" id="stepBadge">Schritt 1 von 4</span>
        </div>
        <div class="progress mb-3" role="progressbar" aria-label="Fortschritt">
          <div id="stepProgress" class="progress-bar bg-success" style="width: 25%;"></div>
        </div>
        <div class="d-flex flex-wrap gap-2 small" id="stepIndicators">
          <button type="button" class="step-pill active" data-step-indicator="1">1. Größe</button>
          <button type="button" class="step-pill" data-step-indicator="2">2. Zutaten</button>
          <button type="button" class="step-pill" data-step-indicator="3">3. Anpassung</button>
          <button type="button" class="step-pill" data-step-indicator="4">4. Zusammenfassung</button>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <section class="config-step is-active" data-step="1">
          <h2 class="h5">Schritt 1: Größe wählen</h2>
          <p class="text-muted">Wähle zuerst die Becher-Größe oder lade ein Preset.</p>

          <div class="row g-3 mb-4">
            <?php foreach ($sizes as $size): ?>
              <div class="col-md-4">
                <input
                  class="btn-check size-input"
                  type="radio"
                  name="size_id"
                  id="size-<?= (int) $size['id'] ?>"
                  value="<?= (int) $size['id'] ?>"
                >
                <label class="card h-100 size-card selectable-card" for="size-<?= (int) $size['id'] ?>">
                  <div class="card-body text-center">
                    <h3 class="h6 mb-1"><?= e((string) $size['name']) ?></h3>
                    <p class="text-muted small mb-2"><?= (int) $size['ml'] ?> ml</p>
                    <p class="fs-5 fw-bold text-success mb-0">EUR <?= number_format((float) $size['base_price'], 2, ',', '.') ?></p>
                  </div>
                </label>
              </div>
            <?php endforeach; ?>
          </div>

          <h3 class="h6 mb-3">Preset laden (Zusatzfeature)</h3>
          <div class="row g-3">
            <?php foreach ($presets as $preset): ?>
              <div class="col-md-6">
                <div class="card h-100 border-secondary-subtle">
                  <div class="card-body">
                    <div class="d-flex justify-content-between gap-2">
                      <div>
                        <h4 class="h6 mb-1"><?= e($preset['name']) ?></h4>
                        <p class="small text-muted mb-2"><?= e($preset['description']) ?></p>
                        <p class="small mb-0 text-muted">
                          <?= e($preset['size_name']) ?> (<?= (int) $preset['size_ml'] ?> ml) · <?= e($preset['ingredient_names']) ?>
                        </p>
                      </div>
                      <button
                        type="button"
                        class="btn btn-outline-success btn-sm js-load-preset"
                        data-preset-id="<?= (int) $preset['id'] ?>"
                      >Preset laden</button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="config-step" data-step="2">
          <h2 class="h5">Schritt 2: Zutaten wählen (24 Optionen)</h2>
          <p class="text-muted">Mindestens eine Zutat auswählen. Suche und Filter helfen bei der Auswahl.</p>

          <div class="row g-2 mb-3">
            <div class="col-md-8">
              <input type="search" id="ingredientSearch" class="form-control" placeholder="Zutat suchen...">
            </div>
            <div class="col-md-4">
              <select id="ingredientCategory" class="form-select">
                <option value="all">Alle Kategorien</option>
                <option value="fruit">Obst</option>
                <option value="vegetable">Gemüse</option>
                <option value="protein">Protein</option>
              </select>
            </div>
          </div>

          <div class="row g-3" id="ingredientsGrid">
            <?php foreach ($ingredients as $ingredient): ?>
              <?php $ingredientName = (string) $ingredient['name']; ?>
              <div
                class="col-sm-6 col-xl-4 ingredient-item"
                data-category="<?= e((string) $ingredient['category']) ?>"
                data-name="<?= e(strtolower($ingredientName)) ?>"
              >
                <div class="card h-100 ingredient-card selectable-card" data-ingredient-card>
                  <img src="<?= e((string) $ingredient['image_url']) ?>" class="card-img-top ingredient-image" alt="<?= e($ingredientName) ?>">
                  <div class="card-body">
                    <div class="form-check mb-2">
                      <input class="form-check-input ingredient-checkbox" type="checkbox" value="<?= (int) $ingredient['id'] ?>" id="ingredient-<?= (int) $ingredient['id'] ?>">
                      <label class="form-check-label fw-semibold" for="ingredient-<?= (int) $ingredient['id'] ?>">
                        <?= e($ingredientName) ?>
                      </label>
                    </div>
                    <div class="small text-muted mb-2">
                      Kategorie: <?= e($categoryLabels[(string) $ingredient['category']] ?? (string) $ingredient['category']) ?>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                      <?php if ((int) $ingredient['is_vegan'] === 1): ?><span class="badge text-bg-light border">Vegan</span><?php endif; ?>
                      <?php if ((int) $ingredient['is_lactose_free'] === 1): ?><span class="badge text-bg-light border">Laktosefrei</span><?php endif; ?>
                      <?php if ((int) $ingredient['is_high_protein'] === 1): ?><span class="badge text-bg-light border">High Protein</span><?php endif; ?>
                      <?php if ((int) $ingredient['is_low_sugar'] === 1): ?><span class="badge text-bg-light border">Low Sugar</span><?php endif; ?>
                    </div>
                    <div class="fw-bold text-success">EUR <?= number_format((float) $ingredient['price'], 2, ',', '.') ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-3 small text-muted">Ausgewählt: <span id="selectedIngredientCount">0</span> Zutaten</div>
        </section>

        <section class="config-step" data-step="3">
          <h2 class="h5">Schritt 3: Individuelle Anpassung</h2>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label for="sweetness" class="form-label">Süßgrad</label>
              <select id="sweetness" class="form-select">
                <option value="none">Kein Zucker</option>
                <option value="low">Wenig</option>
                <option value="medium" selected>Mittel</option>
                <option value="high">Süß</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="consistency" class="form-label">Konsistenz</label>
              <select id="consistency" class="form-select">
                <option value="liquid">Flüssig</option>
                <option value="standard" selected>Standard</option>
                <option value="creamy">Cremig</option>
                <option value="extra_creamy">Extra cremig</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="temperature" class="form-label">Temperatur</label>
              <select id="temperature" class="form-select">
                <option value="chilled" selected>Gekühlt</option>
                <option value="extra_cold">Extra kalt</option>
                <option value="frozen">Frozen</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="sweetener_type" class="form-label">Süßungsmittel</label>
              <select id="sweetener_type" class="form-select">
                <option value="none" selected>ohne</option>
                <option value="honey">Honig</option>
                <option value="agave">Agave</option>
              </select>
            </div>
          </div>

          <h3 class="h6">Toppings</h3>
          <div class="row g-2 mb-4">
            <?php foreach ($toppings as $topping): ?>
              <div class="col-md-6">
                <label class="card p-3 d-flex flex-row justify-content-between align-items-center topping-card selectable-card">
                  <span>
                    <input class="form-check-input me-2 topping-checkbox" type="checkbox" value="<?= (int) $topping['id'] ?>">
                    <?= e((string) $topping['name']) ?>
                  </span>
                  <strong class="text-success">+ EUR <?= number_format((float) $topping['price'], 2, ',', '.') ?></strong>
                </label>
              </div>
            <?php endforeach; ?>
          </div>

          <h3 class="h6">Gutscheincode (Zusatzfeature)</h3>
          <div class="input-group mb-2">
            <input type="text" id="couponCode" class="form-control" placeholder="z. B. FIT10" maxlength="50">
            <button type="button" class="btn btn-outline-success" id="applyCouponBtn">Gutschein prüfen</button>
          </div>
          <div id="couponMessage" class="small"></div>
        </section>

        <section class="config-step" data-step="4">
          <h2 class="h5">Schritt 4: Zusammenfassung</h2>
          <p class="text-muted">Prüfe deine Konfiguration und bestelle danach.</p>

          <div class="mb-3">
            <label for="configurationName" class="form-label">Name der Konfiguration</label>
            <input type="text" id="configurationName" class="form-control" maxlength="120" value="Mein Smoothie">
          </div>

          <div class="card border-0 bg-light mb-3">
            <div class="card-body" id="summaryContainer"></div>
          </div>

          <button type="button" id="orderNowBtn" class="btn btn-success btn-lg">Jetzt bestellen</button>
          <div id="orderMessage" class="small mt-2"></div>
        </section>

        <div class="d-flex justify-content-between border-top pt-4 mt-4">
          <button type="button" id="prevStepBtn" class="btn btn-outline-secondary" disabled>Zurück</button>
          <button type="button" id="nextStepBtn" class="btn btn-success">Weiter</button>
        </div>
        <div id="stepValidationMessage" class="small mt-3"></div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="sticky-top-config">
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h2 class="h6 mb-3">Live-Vorschau</h2>
          <div class="smoothie-visualizer mx-auto" id="smoothieVisualizer">
            <div class="smoothie-glass">
              <div class="smoothie-liquid" id="smoothieLiquid"></div>
            </div>
          </div>
          <div class="small text-muted mt-3" id="visualizerInfo">Wähle Größe und Zutaten, um den Smoothie zu sehen.</div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h6">Preisübersicht</h2>
          <div class="d-flex justify-content-between small mb-2"><span>Basispreis</span><span id="priceBase">EUR 0,00</span></div>
          <div class="d-flex justify-content-between small mb-2"><span>Zutaten</span><span id="priceIngredients">EUR 0,00</span></div>
          <div class="d-flex justify-content-between small mb-2"><span>Toppings</span><span id="priceToppings">EUR 0,00</span></div>
          <div class="d-flex justify-content-between small mb-3"><span>Rabatt</span><span id="priceDiscount">- EUR 0,00</span></div>
          <hr>
          <div class="d-flex justify-content-between fw-bold fs-5"><span>Gesamt</span><span id="priceTotal">EUR 0,00</span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="application/json" id="config-data"><?= json_encode($configData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php include __DIR__ . '/../templates/layout/footer.php'; ?>
