(function () {
  // Script läuft nur auf Seiten mit eingebettetem Konfigurations-JSON.
  const configElement = document.getElementById('config-data');
  if (!configElement) {
    return;
  }

  let configData;
  try {
    configData = JSON.parse(configElement.textContent || '{}');
  } catch (error) {
    console.error('Konfigurationsdaten konnten nicht geladen werden.', error);
    return;
  }

  const sizes = Array.isArray(configData.sizes) ? configData.sizes : [];
  const ingredients = Array.isArray(configData.ingredients) ? configData.ingredients : [];
  const toppings = Array.isArray(configData.toppings) ? configData.toppings : [];
  const presets = Array.isArray(configData.presets) ? configData.presets : [];

  // ---------- DOM-Referenzen ----------
  const stepSections = Array.from(document.querySelectorAll('.config-step'));
  const stepIndicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
  const stepBadge = document.getElementById('stepBadge');
  const stepProgress = document.getElementById('stepProgress');
  const prevStepBtn = document.getElementById('prevStepBtn');
  const nextStepBtn = document.getElementById('nextStepBtn');
  const orderNowBtn = document.getElementById('orderNowBtn');

  const ingredientCheckboxes = Array.from(document.querySelectorAll('.ingredient-checkbox'));
  const ingredientCards = Array.from(document.querySelectorAll('[data-ingredient-card]'));
  const ingredientItems = Array.from(document.querySelectorAll('.ingredient-item'));
  const toppingCheckboxes = Array.from(document.querySelectorAll('.topping-checkbox'));
  const sizeInputs = Array.from(document.querySelectorAll('.size-input'));
  const adjustmentSelects = Array.from(document.querySelectorAll('[data-adjustment-select]'));

  const ingredientSearch = document.getElementById('ingredientSearch');
  const ingredientCategory = document.getElementById('ingredientCategory');

  const selectedIngredientCount = document.getElementById('selectedIngredientCount');
  const couponCodeInput = document.getElementById('couponCode');
  const applyCouponBtn = document.getElementById('applyCouponBtn');
  const couponMessage = document.getElementById('couponMessage');
  const orderMessage = document.getElementById('orderMessage');
  const stepValidationMessage = document.getElementById('stepValidationMessage');
  const configurationNameInput = document.getElementById('configurationName');

  const priceBase = document.getElementById('priceBase');
  const priceIngredients = document.getElementById('priceIngredients');
  const priceToppings = document.getElementById('priceToppings');
  const priceDiscount = document.getElementById('priceDiscount');
  const priceTotal = document.getElementById('priceTotal');

  const summaryContainer = document.getElementById('summaryContainer');
  const smoothieLiquid = document.getElementById('smoothieLiquid');
  const visualizerInfo = document.getElementById('visualizerInfo');

  // ---------- Lookup-Strukturen ----------
  const sizeById = new Map(sizes.map((size) => [Number(size.id), size]));
  const ingredientById = new Map(ingredients.map((ingredient) => [Number(ingredient.id), ingredient]));
  const toppingById = new Map(toppings.map((topping) => [Number(topping.id), topping]));
  const presetById = new Map(presets.map((preset) => [Number(preset.id), preset]));

  // ---------- Konstante Label/Styles ----------
  const rawAdjustments = Array.isArray(configData.adjustments) ? configData.adjustments : [];
  const rawSelectionDefinitions = configData && typeof configData.selectionDefinitions === 'object'
    ? configData.selectionDefinitions
    : {};

  // Vereinheitlicht Backend-Definitionsobjekte in eine robuste JS-Struktur.
  function normalizeAdjustmentDefinition(definition) {
    if (!definition || typeof definition !== 'object') {
      return null;
    }

    const field = typeof definition.field === 'string' ? definition.field.trim() : '';
    if (field === '') {
      return null;
    }

    const jsKey = typeof definition.js_key === 'string' && definition.js_key.trim() !== ''
      ? definition.js_key.trim()
      : field;
    const label = typeof definition.label === 'string' && definition.label.trim() !== ''
      ? definition.label.trim()
      : field;
    const defaultValue = typeof definition.default === 'string' ? definition.default : '';
    const rawOptions = Array.isArray(definition.options) ? definition.options : [];
    const options = rawOptions
      .map((option) => {
        if (!option || typeof option !== 'object') {
          return null;
        }

        const value = typeof option.value === 'string' ? option.value : '';
        if (value === '') {
          return null;
        }

        const optionLabel = typeof option.label === 'string' && option.label !== '' ? option.label : value;
        return { value, label: optionLabel };
      })
      .filter(Boolean);

    return { field, jsKey, label, defaultValue, options };
  }

  const normalizedAdjustments = rawAdjustments
    .map(normalizeAdjustmentDefinition)
    .filter(Boolean);
  const adjustmentSelectByField = new Map(
    adjustmentSelects
      .map((select) => [String(select.getAttribute('data-adjustment-select') || ''), select])
      .filter(([field]) => field !== '')
  );
  const labels = normalizedAdjustments.reduce((accumulator, definition) => {
    const optionMap = {};
    definition.options.forEach((option) => {
      optionMap[option.value] = option.label;
    });
    accumulator[definition.field] = optionMap;
    accumulator[definition.jsKey] = optionMap;
    return accumulator;
  }, {});

  const selectionLabels = {
    size: rawSelectionDefinitions.sizes && typeof rawSelectionDefinitions.sizes === 'object'
      ? String(rawSelectionDefinitions.sizes.item_label_singular || 'Größe')
      : 'Größe',
    toppingPlural: rawSelectionDefinitions.toppings && typeof rawSelectionDefinitions.toppings === 'object'
      ? String(rawSelectionDefinitions.toppings.item_label_plural || 'Toppings')
      : 'Toppings',
  };

  const defaultCategoryColor = {
    fruit: '#ff8fa3',
    vegetable: '#72c878',
    protein: '#d2b48c',
  };
  const backendCategoryColor = configData && typeof configData.ingredientCategoryColors === 'object'
    ? configData.ingredientCategoryColors
    : {};
  const categoryColor = { ...defaultCategoryColor, ...backendCategoryColor };

  const consistencyOpacity = {
    liquid: 0.72,
    standard: 0.84,
    creamy: 0.92,
    extra_creamy: 1,
  };

  // ---------- UI-Status ----------
  const state = {
    currentStep: 1,
    sizeId: null,
    ingredientIds: [],
    toppingIds: [],
    adjustments: {},
    couponCode: '',
    couponApplied: false,
    discountAmount: 0,
  };

  function getAdjustmentValueFromUi(definition) {
    const select = adjustmentSelectByField.get(definition.field);
    if (!select) {
      return definition.defaultValue;
    }

    const value = typeof select.value === 'string' ? select.value : '';
    return value !== '' ? value : definition.defaultValue;
  }

  // Liest einen Anpassungswert tolerant über Feldname und js_key.
  function getAdjustmentStateValue(definition) {
    const valueFromField = state.adjustments[definition.field];
    if (typeof valueFromField === 'string' && valueFromField !== '') {
      return valueFromField;
    }

    const valueFromJsKey = state.adjustments[definition.jsKey];
    if (typeof valueFromJsKey === 'string' && valueFromJsKey !== '') {
      return valueFromJsKey;
    }

    return definition.defaultValue;
  }

  // Hält Feldname und js_key im State synchron, damit Summary + Payload stabil bleiben.
  function setAdjustmentStateValue(definition, value) {
    state.adjustments[definition.field] = value;
    state.adjustments[definition.jsKey] = value;
  }

  // Einheitliches Währungsformat für alle Preisanzeigen.
  function formatCurrency(value) {
    const amount = Number(value) || 0;
    return `EUR ${amount.toFixed(2).replace('.', ',')}`;
  }

  // Escaping schützt vor XSS, da die Summary als innerHTML gerendert wird.
  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getCheckedValues(checkboxes) {
    return checkboxes
      .filter((checkbox) => checkbox.checked)
      .map((checkbox) => Number(checkbox.value));
  }

  // Liest das aktuell gewählte Größen-Objekt aus der ID-Map.
  function getSelectedSize() {
    return state.sizeId ? sizeById.get(Number(state.sizeId)) || null : null;
  }

  function getSelectedIngredients() {
    return state.ingredientIds.map((id) => ingredientById.get(Number(id))).filter(Boolean);
  }

  function getSelectedToppings() {
    return state.toppingIds.map((id) => toppingById.get(Number(id))).filter(Boolean);
  }

  // Preis wird clientseitig sofort neu gerechnet, damit User direkt Feedback bekommen.
  function calculateLocalPricing() {
    const selectedSize = getSelectedSize();
    const selectedIngredients = getSelectedIngredients();
    const selectedToppings = getSelectedToppings();

    const base = selectedSize ? Number(selectedSize.base_price) : 0;
    const ingredientPrice = selectedIngredients.reduce((sum, ingredient) => sum + Number(ingredient.price || 0), 0);
    const toppingPrice = selectedToppings.reduce((sum, topping) => sum + Number(topping.price || 0), 0);
    const subtotal = base + ingredientPrice + toppingPrice;

    let discount = state.couponApplied ? Number(state.discountAmount || 0) : 0;
    if (discount > subtotal) {
      discount = subtotal;
    }

    return {
      base,
      ingredientPrice,
      toppingPrice,
      subtotal,
      discount,
      total: subtotal - discount,
    };
  }

  function clearStepMessage() {
    showStepMessage('', '');
  }

  function ensureSizeSelected() {
    if (state.sizeId) {
      return true;
    }

    showStepMessage('Bitte zuerst eine Größe wählen.', 'danger');
    return false;
  }

  function ensureIngredientsSelected() {
    if (state.ingredientIds.length > 0) {
      return true;
    }

    showStepMessage('Bitte mindestens eine Zutat auswählen.', 'danger');
    return false;
  }

  function validateCurrentStep() {
    if (state.currentStep === 1) {
      return ensureSizeSelected();
    }

    if (state.currentStep === 2) {
      return ensureIngredientsSelected();
    }

    clearStepMessage();
    return true;
  }

  // Direktsprünge sind erlaubt, aber nur wenn Pflichtdaten davor bereits gesetzt sind.
  function canNavigateToStep(targetStep) {
    if (targetStep <= 1) {
      clearStepMessage();
      return true;
    }

    if (!ensureSizeSelected()) {
      return false;
    }

    if (targetStep >= 3 && !ensureIngredientsSelected()) {
      return false;
    }

    clearStepMessage();
    return true;
  }

  function setStep(stepNumber) {
    // Sichtbarkeit der Step-Inhalte, Step-Pills und Navigation synchronisieren.
    state.currentStep = stepNumber;

    stepSections.forEach((section) => {
      const sectionStep = Number(section.getAttribute('data-step'));
      section.classList.toggle('is-active', sectionStep === state.currentStep);
    });

    stepIndicators.forEach((indicator) => {
      const indicatorStep = Number(indicator.getAttribute('data-step-indicator'));
      indicator.classList.toggle('active', indicatorStep === state.currentStep);
    });

    if (stepBadge) {
      stepBadge.textContent = `Schritt ${state.currentStep} von 4`;
    }

    if (stepProgress) {
      stepProgress.style.width = `${state.currentStep * 25}%`;
    }

    if (prevStepBtn) {
      prevStepBtn.disabled = state.currentStep === 1;
    }

    if (nextStepBtn) {
      nextStepBtn.classList.toggle('d-none', state.currentStep === 4);
    }

    clearStepMessage();
  }

  function updateSelectedCardClasses() {
    // Optisches Highlight der aktuell ausgewählten Zutaten/Toppings.
    ingredientCheckboxes.forEach((checkbox) => {
      const card = checkbox.closest('[data-ingredient-card]');
      if (card) {
        card.classList.toggle('is-selected', checkbox.checked);
      }
    });

    toppingCheckboxes.forEach((checkbox) => {
      const card = checkbox.closest('.topping-card');
      if (card) {
        card.classList.toggle('is-selected', checkbox.checked);
      }
    });
  }

  function updateIngredientCount() {
    if (selectedIngredientCount) {
      selectedIngredientCount.textContent = String(state.ingredientIds.length);
    }
  }

  function updatePriceOverview() {
    const pricing = calculateLocalPricing();

    if (priceBase) {
      priceBase.textContent = formatCurrency(pricing.base);
    }

    if (priceIngredients) {
      priceIngredients.textContent = formatCurrency(pricing.ingredientPrice);
    }

    if (priceToppings) {
      priceToppings.textContent = formatCurrency(pricing.toppingPrice);
    }

    if (priceDiscount) {
      priceDiscount.textContent = `- ${formatCurrency(pricing.discount)}`;
    }

    if (priceTotal) {
      priceTotal.textContent = formatCurrency(pricing.total);
    }
  }

  function rgbToHex(red, green, blue) {
    const values = [red, green, blue].map((value) => {
      const normalized = Math.max(0, Math.min(255, Math.round(value)));
      return normalized.toString(16).padStart(2, '0');
    });

    return `#${values.join('')}`;
  }

  function hexToRgb(hexColor) {
    const sanitized = hexColor.replace('#', '');
    const value = sanitized.length === 3
      ? sanitized.split('').map((char) => char + char).join('')
      : sanitized;

    return {
      r: parseInt(value.slice(0, 2), 16),
      g: parseInt(value.slice(2, 4), 16),
      b: parseInt(value.slice(4, 6), 16),
    };
  }

  function getSmoothieColor() {
    const selectedIngredients = getSelectedIngredients();
    if (selectedIngredients.length === 0) {
      return '#d9dee4';
    }

    const rgbColors = selectedIngredients.map((ingredient) => {
      const color = categoryColor[ingredient.category] || '#9ec5fe';
      return hexToRgb(color);
    });

    const sum = rgbColors.reduce(
      (accumulator, color) => ({
        r: accumulator.r + color.r,
        g: accumulator.g + color.g,
        b: accumulator.b + color.b,
      }),
      { r: 0, g: 0, b: 0 }
    );

    return rgbToHex(sum.r / rgbColors.length, sum.g / rgbColors.length, sum.b / rgbColors.length);
  }

  // Visualisierung ist bewusst einfach: Füllhöhe + Farbton + Opazität.
  function updateVisualizer() {
    if (!smoothieLiquid) {
      return;
    }

    const selectedSize = getSelectedSize();
    const selectedIngredients = getSelectedIngredients();

    let fillPercent = 22;
    if (selectedSize) {
      if (Number(selectedSize.ml) >= 700) {
        fillPercent = 80;
      } else if (Number(selectedSize.ml) >= 500) {
        fillPercent = 68;
      } else {
        fillPercent = 55;
      }
    }

    fillPercent += Math.min(selectedIngredients.length * 1.5, 15);
    fillPercent = Math.min(fillPercent, 92);

    smoothieLiquid.style.height = `${fillPercent}%`;
    smoothieLiquid.style.backgroundColor = getSmoothieColor();
    const consistencyValue = state.adjustments.consistency || 'standard';
    smoothieLiquid.style.opacity = String(consistencyOpacity[consistencyValue] || 0.84);

    if (!visualizerInfo) {
      return;
    }

    if (!selectedSize && selectedIngredients.length === 0) {
      visualizerInfo.textContent = 'Wähle Größe und Zutaten, um den Smoothie zu sehen.';
      return;
    }

    const sizeText = selectedSize ? `${selectedSize.name} (${selectedSize.ml} ml)` : 'ohne Größe';
    visualizerInfo.textContent = `${sizeText} mit ${selectedIngredients.length} Zutaten`;
  }

  function updateSummary() {
    // Zusammenfassung wird komplett neu gerendert, damit alle Werte konsistent bleiben.
    if (!summaryContainer) {
      return;
    }

    const selectedSize = getSelectedSize();
    const selectedIngredients = getSelectedIngredients();
    const selectedToppings = getSelectedToppings();
    const pricing = calculateLocalPricing();

    const ingredientsList = selectedIngredients.length > 0
      ? selectedIngredients.map((ingredient) => `${escapeHtml(ingredient.name)} (${formatCurrency(ingredient.price)})`).join('<br>')
      : 'Keine Zutat ausgewählt';

    const toppingsList = selectedToppings.length > 0
      ? selectedToppings.map((topping) => `${escapeHtml(topping.name)} (${formatCurrency(topping.price)})`).join('<br>')
      : 'Keine Toppings';

    const sizeText = selectedSize
      ? `${escapeHtml(selectedSize.name)} (${Number(selectedSize.ml)} ml)`
      : 'Nicht ausgewählt';

    const adjustmentLines = normalizedAdjustments.map((definition) => {
      const selectedValue = getAdjustmentStateValue(definition);
      const labelMap = labels[definition.field] || labels[definition.jsKey] || {};
      const selectedLabel = labelMap[selectedValue] || selectedValue;
      return `${escapeHtml(definition.label)}: ${escapeHtml(selectedLabel)}`;
    });
    const adjustmentText = adjustmentLines.length > 0
      ? adjustmentLines.join('<br>')
      : 'Keine Anpassung';
    const couponText = escapeHtml(state.couponCode);

    summaryContainer.innerHTML = `
      <div class="row g-3 small">
        <div class="col-md-6">
          <strong>${escapeHtml(selectionLabels.size)}</strong><br>
          ${sizeText}
        </div>
        <div class="col-md-6">
          <strong>Anpassung</strong><br>
          ${adjustmentText}
        </div>
        <div class="col-md-6">
          <strong>Zutaten (${selectedIngredients.length})</strong><br>
          ${ingredientsList}
        </div>
        <div class="col-md-6">
          <strong>${escapeHtml(selectionLabels.toppingPlural)} (${selectedToppings.length})</strong><br>
          ${toppingsList}
        </div>
      </div>
      <hr>
      <div class="d-flex justify-content-between"><span>Zwischensumme</span><strong>${formatCurrency(pricing.subtotal)}</strong></div>
      <div class="d-flex justify-content-between"><span>Rabatt</span><strong>- ${formatCurrency(pricing.discount)}</strong></div>
      <div class="d-flex justify-content-between fs-5 mt-2"><span>Gesamt</span><strong>${formatCurrency(pricing.total)}</strong></div>
      ${state.couponApplied && state.couponCode ? `<div class="small text-success mt-2">Gutschein aktiv: ${couponText}</div>` : ''}
    `;
  }

  function filterIngredients() {
    // Clientseitiger Filter: Suche + Kategorie werden kombiniert.
    const search = (ingredientSearch ? ingredientSearch.value : '').trim().toLowerCase();
    const category = ingredientCategory ? ingredientCategory.value : 'all';

    ingredientItems.forEach((item) => {
      const itemName = (item.getAttribute('data-name') || '').toLowerCase();
      const itemCategory = item.getAttribute('data-category') || '';

      const matchesSearch = search === '' || itemName.includes(search);
      const matchesCategory = category === 'all' || itemCategory === category;
      item.classList.toggle('d-none', !(matchesSearch && matchesCategory));
    });
  }

  function setMessage(target, baseClass, text, type) {
    if (!target) {
      return;
    }

    target.textContent = text;
    target.className = `${baseClass} ${type ? `text-${type}` : ''}`.trim();
  }

  function showCouponMessage(text, type) {
    setMessage(couponMessage, 'small', text, type);
  }

  function showOrderMessage(text, type) {
    setMessage(orderMessage, 'small mt-2', text, type);
  }

  function showStepMessage(text, type) {
    setMessage(stepValidationMessage, 'small mt-3', text, type);
  }

  function handleIngredientCardClick(event) {
    if (!(event.target instanceof Element)) {
      return;
    }

    // Klicks auf echte Form-Elemente sollen deren Standardverhalten behalten.
    if (event.target.closest('a, button, input, label, select, textarea')) {
      return;
    }

    const card = event.currentTarget;
    if (!(card instanceof Element)) {
      return;
    }

    const checkbox = card.querySelector('.ingredient-checkbox');
    if (!(checkbox instanceof HTMLInputElement)) {
      return;
    }

    checkbox.checked = !checkbox.checked;
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function refreshUi() {
    // Zentrale Render-Pipeline nach jeder relevanten User-Aktion.
    updateSelectedCardClasses();
    updateIngredientCount();
    updatePriceOverview();
    updateSummary();
    updateVisualizer();
  }

  function readSelectionsFromUi() {
    // Überträgt den Zustand aus dem DOM in das zentrale state-Objekt.
    const selectedSizeInput = sizeInputs.find((input) => input.checked);
    state.sizeId = selectedSizeInput ? Number(selectedSizeInput.value) : null;
    state.ingredientIds = getCheckedValues(ingredientCheckboxes);
    state.toppingIds = getCheckedValues(toppingCheckboxes);
    normalizedAdjustments.forEach((definition) => {
      setAdjustmentStateValue(definition, getAdjustmentValueFromUi(definition));
    });
  }

  function syncUiFromState() {
    // Nutzt man z. B. beim Preset-Laden: State -> DOM.
    sizeInputs.forEach((input) => {
      input.checked = Number(input.value) === Number(state.sizeId);
    });

    ingredientCheckboxes.forEach((checkbox) => {
      checkbox.checked = state.ingredientIds.includes(Number(checkbox.value));
    });

    toppingCheckboxes.forEach((checkbox) => {
      checkbox.checked = state.toppingIds.includes(Number(checkbox.value));
    });

    normalizedAdjustments.forEach((definition) => {
      const select = adjustmentSelectByField.get(definition.field);
      if (!select) {
        return;
      }

      const allowedValues = new Set(definition.options.map((option) => option.value));
      const currentValue = getAdjustmentStateValue(definition);
      const nextValue = allowedValues.has(currentValue) ? currentValue : definition.defaultValue;
      setAdjustmentStateValue(definition, nextValue);
      select.value = nextValue;
    });
  }

  function resetCouponState() {
    state.couponApplied = false;
    state.discountAmount = 0;
  }

  function markCouponDirty() {
    // Sobald relevante Auswahl geändert wird, ist ein alter Gutschein-Check ungültig.
    if (!state.couponApplied && state.discountAmount === 0) {
      return;
    }

    resetCouponState();

    if (state.couponCode) {
      showCouponMessage('Auswahl geändert. Gutschein bitte erneut prüfen.', 'warning');
    }
  }

  async function postJson(url, payload) {
    // Einheitlicher JSON-POST-Wrapper inklusive CSRF und robustem JSON-Parsing.
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': configData.csrfToken,
      },
      body: JSON.stringify({
        ...payload,
        csrf_token: configData.csrfToken,
      }),
    });

    let data = {};
    try {
      data = await response.json();
    } catch (error) {
      data = {};
    }

    return { response, data };
  }

  async function applyCoupon() {
    // Prüft den Gutschein serverseitig und aktualisiert den lokalen Rabattzustand.
    const code = (couponCodeInput ? couponCodeInput.value : '').trim().toUpperCase();

    if (!ensureSizeSelected()) {
      showCouponMessage('Bitte zuerst eine Größe wählen.', 'danger');
      return;
    }

    if (!ensureIngredientsSelected()) {
      showCouponMessage('Bitte zuerst Zutaten auswählen.', 'danger');
      return;
    }

    if (code === '') {
      state.couponCode = '';
      resetCouponState();
      showCouponMessage('Bitte einen Gutscheincode eingeben.', 'danger');
      refreshUi();
      return;
    }

    showCouponMessage('Gutschein wird geprüft...', 'muted');

    try {
      const { response, data } = await postJson(configData.api.applyCoupon, {
        size_id: state.sizeId,
        ingredient_ids: state.ingredientIds,
        topping_ids: state.toppingIds,
        coupon_code: code,
      });

      state.couponCode = code;
      state.couponApplied = Boolean(data.valid);
      state.discountAmount = Number(data.pricing && data.pricing.discount_amount ? data.pricing.discount_amount : 0);

      if (response.ok && data.valid) {
        showCouponMessage(data.message || 'Gutschein wurde angewendet.', 'success');
      } else {
        resetCouponState();
        showCouponMessage(data.message || 'Gutschein konnte nicht angewendet werden.', 'danger');
      }

      refreshUi();
    } catch (error) {
      resetCouponState();
      showCouponMessage('Fehler bei der Gutscheinprüfung.', 'danger');
      refreshUi();
    }
  }

  async function saveConfiguration() {
    // Persistiert die finale Konfiguration und leitet bei Erfolg zum Dashboard weiter.
    if (!ensureSizeSelected()) {
      showOrderMessage('Bitte zuerst eine Größe wählen.', 'danger');
      return;
    }

    if (!ensureIngredientsSelected()) {
      showOrderMessage('Bitte mindestens eine Zutat auswählen.', 'danger');
      return;
    }

    const configurationName = configurationNameInput ? configurationNameInput.value : 'Mein Smoothie';
    showOrderMessage('Konfiguration wird gespeichert...', 'muted');

    const adjustmentsPayload = {};
    normalizedAdjustments.forEach((definition) => {
      adjustmentsPayload[definition.field] = getAdjustmentStateValue(definition);
    });

    try {
      const { response, data } = await postJson(configData.api.saveConfiguration, {
        name: configurationName,
        size_id: state.sizeId,
        ingredient_ids: state.ingredientIds,
        topping_ids: state.toppingIds,
        adjustments: adjustmentsPayload,
        ...adjustmentsPayload,
        coupon_code: state.couponApplied ? state.couponCode : '',
      });

      if (!response.ok || !data.success) {
        showOrderMessage(data.message || 'Speichern fehlgeschlagen.', 'danger');

        if (data.redirect_url) {
          setTimeout(() => {
            window.location.href = data.redirect_url;
          }, 800);
        }
        return;
      }

      showOrderMessage('Gespeichert. Weiterleitung zum Dashboard...', 'success');
      setTimeout(() => {
        window.location.href = data.redirect_url || 'dashboard.php';
      }, 700);
    } catch (error) {
      showOrderMessage('Speichern fehlgeschlagen. Bitte erneut versuchen.', 'danger');
    }
  }

  // Ein Preset setzt Größe/Zutaten/Anpassungen auf einen vordefinierten Zustand.
  function loadPreset(presetId) {
    const preset = presetById.get(Number(presetId));
    if (!preset) {
      return;
    }

    state.sizeId = Number(preset.size_id);
    state.ingredientIds = Array.isArray(preset.ingredient_ids)
      ? preset.ingredient_ids.map((id) => Number(id))
      : [];
    state.toppingIds = [];
    normalizedAdjustments.forEach((definition) => {
      const rawValue = preset[definition.field] ?? preset[definition.jsKey] ?? definition.defaultValue;
      const nextValue = typeof rawValue === 'string' ? rawValue : definition.defaultValue;
      setAdjustmentStateValue(definition, nextValue);
    });

    markCouponDirty();
    syncUiFromState();
    refreshUi();
    showOrderMessage(`Preset "${preset.name}" wurde geladen.`, 'success');
  }

  function handleSelectionChange(invalidateCoupon = true) {
    // Gemeinsamer Handler für alle "Auswahl geändert"-Events.
    readSelectionsFromUi();
    if (invalidateCoupon) {
      markCouponDirty();
    }
    refreshUi();
  }

  // ---------- Event-Bindings ----------
  sizeInputs.forEach((input) => {
    input.addEventListener('change', () => handleSelectionChange(true));
  });

  ingredientCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', () => handleSelectionChange(true));
  });

  toppingCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', () => handleSelectionChange(true));
  });

  ingredientCards.forEach((card) => {
    card.addEventListener('click', handleIngredientCardClick);
  });

  normalizedAdjustments.forEach((definition) => {
    const select = adjustmentSelectByField.get(definition.field);
    if (!select) {
      return;
    }

    select.addEventListener('change', () => handleSelectionChange(false));
  });

  if (ingredientSearch) {
    ingredientSearch.addEventListener('input', filterIngredients);
  }

  if (ingredientCategory) {
    ingredientCategory.addEventListener('change', filterIngredients);
  }

  document.querySelectorAll('.js-load-preset').forEach((button) => {
    button.addEventListener('click', () => {
      const presetId = Number(button.getAttribute('data-preset-id'));
      loadPreset(presetId);
    });
  });

  if (prevStepBtn) {
    prevStepBtn.addEventListener('click', () => {
      if (state.currentStep > 1) {
        setStep(state.currentStep - 1);
      }
    });
  }

  if (nextStepBtn) {
    nextStepBtn.addEventListener('click', () => {
      if (!validateCurrentStep()) {
        return;
      }

      if (state.currentStep < 4) {
        setStep(state.currentStep + 1);
      }
    });
  }

  stepIndicators.forEach((indicator) => {
    indicator.addEventListener('click', () => {
      const targetStep = Number(indicator.getAttribute('data-step-indicator'));
      if (!Number.isInteger(targetStep) || targetStep < 1 || targetStep > 4) {
        return;
      }

      if (targetStep === state.currentStep) {
        return;
      }

      if (targetStep > state.currentStep && !canNavigateToStep(targetStep)) {
        return;
      }

      setStep(targetStep);
    });
  });

  if (applyCouponBtn) {
    applyCouponBtn.addEventListener('click', applyCoupon);
  }

  if (orderNowBtn) {
    orderNowBtn.addEventListener('click', saveConfiguration);
  }

  if (couponCodeInput) {
    couponCodeInput.addEventListener('input', () => {
      state.couponCode = couponCodeInput.value.trim().toUpperCase();
      markCouponDirty();
      refreshUi();
    });
  }

  // ---------- Initialisierung ----------
  readSelectionsFromUi();
  filterIngredients();
  setStep(1);
  refreshUi();
})();
