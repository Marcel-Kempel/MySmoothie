(function () {
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

  const stepSections = Array.from(document.querySelectorAll('.config-step'));
  const stepIndicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
  const stepBadge = document.getElementById('stepBadge');
  const stepProgress = document.getElementById('stepProgress');
  const prevStepBtn = document.getElementById('prevStepBtn');
  const nextStepBtn = document.getElementById('nextStepBtn');
  const orderNowBtn = document.getElementById('orderNowBtn');

  const ingredientCheckboxes = Array.from(document.querySelectorAll('.ingredient-checkbox'));
  const ingredientCards = Array.from(document.querySelectorAll('[data-ingredient-card]'));
  const toppingCheckboxes = Array.from(document.querySelectorAll('.topping-checkbox'));
  const sizeInputs = Array.from(document.querySelectorAll('.size-input'));

  const sweetnessSelect = document.getElementById('sweetness');
  const consistencySelect = document.getElementById('consistency');
  const temperatureSelect = document.getElementById('temperature');
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

  const sizeById = new Map(configData.sizes.map((size) => [Number(size.id), size]));
  const ingredientById = new Map(configData.ingredients.map((ingredient) => [Number(ingredient.id), ingredient]));
  const toppingById = new Map(configData.toppings.map((topping) => [Number(topping.id), topping]));
  const presetById = new Map(configData.presets.map((preset) => [Number(preset.id), preset]));

  const state = {
    currentStep: 1,
    sizeId: null,
    ingredientIds: [],
    toppingIds: [],
    sweetness: sweetnessSelect ? sweetnessSelect.value : 'medium',
    consistency: consistencySelect ? consistencySelect.value : 'standard',
    temperature: temperatureSelect ? temperatureSelect.value : 'chilled',
    couponCode: '',
    couponApplied: false,
    discountAmount: 0,
  };

  function formatCurrency(value) {
    const amount = Number(value) || 0;
    return `EUR ${amount.toFixed(2).replace('.', ',')}`;
  }

  function getSelectedSize() {
    return state.sizeId ? sizeById.get(Number(state.sizeId)) || null : null;
  }

  function getSelectedIngredients() {
    return state.ingredientIds.map((id) => ingredientById.get(Number(id))).filter(Boolean);
  }

  function getSelectedToppings() {
    return state.toppingIds.map((id) => toppingById.get(Number(id))).filter(Boolean);
  }

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

    const total = subtotal - discount;

    return {
      base,
      ingredientPrice,
      toppingPrice,
      subtotal,
      discount,
      total,
    };
  }

  function markCouponDirty() {
    if (!state.couponApplied && state.discountAmount === 0) {
      return;
    }

    state.couponApplied = false;
    state.discountAmount = 0;

    if (couponMessage && state.couponCode) {
      couponMessage.textContent = 'Auswahl geändert. Gutschein bitte erneut prüfen.';
      couponMessage.className = 'small text-warning';
    }
  }

  function setStep(stepNumber) {
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
      if (state.currentStep === 4) {
        nextStepBtn.classList.add('d-none');
      } else {
        nextStepBtn.classList.remove('d-none');
      }
    }

    showStepMessage('', '');
  }

  function validateCurrentStep() {
    if (state.currentStep === 1 && !state.sizeId) {
      showStepMessage('Bitte zuerst eine Größe wählen.', 'danger');
      return false;
    }

    if (state.currentStep === 2 && state.ingredientIds.length === 0) {
      showStepMessage('Bitte mindestens eine Zutat auswählen.', 'danger');
      return false;
    }

    showStepMessage('', '');
    return true;
  }

  function canNavigateToStep(targetStep) {
    if (targetStep <= 1) {
      return true;
    }

    if (!state.sizeId) {
      showStepMessage('Bitte zuerst eine Größe wählen.', 'danger');
      return false;
    }

    if (targetStep >= 3 && state.ingredientIds.length === 0) {
      showStepMessage('Bitte mindestens eine Zutat auswählen.', 'danger');
      return false;
    }

    showStepMessage('', '');
    return true;
  }

  function updateSelectedCardClasses() {
    ingredientCheckboxes.forEach((checkbox) => {
      const card = checkbox.closest('[data-ingredient-card]');
      if (!card) {
        return;
      }
      card.classList.toggle('is-selected', checkbox.checked);
    });

    toppingCheckboxes.forEach((checkbox) => {
      const card = checkbox.closest('.topping-card');
      if (!card) {
        return;
      }
      card.classList.toggle('is-selected', checkbox.checked);
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

    const categoryColor = {
      fruit: '#ff8fa3',
      vegetable: '#72c878',
      protein: '#d2b48c',
    };

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

    const consistencyOpacity = {
      liquid: 0.72,
      standard: 0.84,
      creamy: 0.92,
      extra_creamy: 1,
    };

    smoothieLiquid.style.height = `${fillPercent}%`;
    smoothieLiquid.style.backgroundColor = getSmoothieColor();
    smoothieLiquid.style.opacity = String(consistencyOpacity[state.consistency] || 0.84);

    if (visualizerInfo) {
      if (!selectedSize && selectedIngredients.length === 0) {
        visualizerInfo.textContent = 'Wähle Größe und Zutaten, um den Smoothie zu sehen.';
      } else {
        const sizeText = selectedSize ? `${selectedSize.name} (${selectedSize.ml} ml)` : 'ohne Größe';
        visualizerInfo.textContent = `${sizeText} mit ${selectedIngredients.length} Zutaten`;
      }
    }
  }

  function updateSummary() {
    if (!summaryContainer) {
      return;
    }

    const selectedSize = getSelectedSize();
    const selectedIngredients = getSelectedIngredients();
    const selectedToppings = getSelectedToppings();
    const pricing = calculateLocalPricing();

    const sweetnessLabels = {
      none: 'Kein Zucker',
      low: 'Wenig',
      medium: 'Mittel',
      high: 'Süß',
    };

    const consistencyLabels = {
      liquid: 'Flüssig',
      standard: 'Standard',
      creamy: 'Cremig',
      extra_creamy: 'Extra cremig',
    };

    const temperatureLabels = {
      chilled: 'Gekühlt',
      extra_cold: 'Extra kalt',
      frozen: 'Frozen',
    };

    const ingredientsList = selectedIngredients.length > 0
      ? selectedIngredients.map((ingredient) => `${ingredient.name} (${formatCurrency(ingredient.price)})`).join('<br>')
      : 'Keine Zutat ausgewählt';

    const toppingsList = selectedToppings.length > 0
      ? selectedToppings.map((topping) => `${topping.name} (${formatCurrency(topping.price)})`).join('<br>')
      : 'Keine Toppings';

    summaryContainer.innerHTML = `
      <div class="row g-3 small">
        <div class="col-md-6">
          <strong>Größe</strong><br>
          ${selectedSize ? `${selectedSize.name} (${selectedSize.ml} ml)` : 'Nicht ausgewählt'}
        </div>
        <div class="col-md-6">
          <strong>Anpassung</strong><br>
          Süßgrad: ${sweetnessLabels[state.sweetness] || state.sweetness}<br>
          Konsistenz: ${consistencyLabels[state.consistency] || state.consistency}<br>
          Temperatur: ${temperatureLabels[state.temperature] || state.temperature}
        </div>
        <div class="col-md-6">
          <strong>Zutaten (${selectedIngredients.length})</strong><br>
          ${ingredientsList}
        </div>
        <div class="col-md-6">
          <strong>Toppings (${selectedToppings.length})</strong><br>
          ${toppingsList}
        </div>
      </div>
      <hr>
      <div class="d-flex justify-content-between"><span>Zwischensumme</span><strong>${formatCurrency(pricing.subtotal)}</strong></div>
      <div class="d-flex justify-content-between"><span>Rabatt</span><strong>- ${formatCurrency(pricing.discount)}</strong></div>
      <div class="d-flex justify-content-between fs-5 mt-2"><span>Gesamt</span><strong>${formatCurrency(pricing.total)}</strong></div>
      ${state.couponApplied && state.couponCode ? `<div class="small text-success mt-2">Gutschein aktiv: ${state.couponCode}</div>` : ''}
    `;
  }

  function filterIngredients() {
    const search = (ingredientSearch ? ingredientSearch.value : '').trim().toLowerCase();
    const category = ingredientCategory ? ingredientCategory.value : 'all';

    const items = Array.from(document.querySelectorAll('.ingredient-item'));
    items.forEach((item) => {
      const itemName = (item.getAttribute('data-name') || '').toLowerCase();
      const itemCategory = item.getAttribute('data-category') || '';

      const matchesSearch = search === '' || itemName.includes(search);
      const matchesCategory = category === 'all' || itemCategory === category;

      item.classList.toggle('d-none', !(matchesSearch && matchesCategory));
    });
  }

  function showCouponMessage(text, type) {
    if (!couponMessage) {
      return;
    }

    couponMessage.textContent = text;
    couponMessage.className = `small ${type ? `text-${type}` : ''}`;
  }

  function showOrderMessage(text, type) {
    if (!orderMessage) {
      return;
    }

    orderMessage.textContent = text;
    orderMessage.className = `small mt-2 ${type ? `text-${type}` : ''}`;
  }

  function showStepMessage(text, type) {
    if (!stepValidationMessage) {
      return;
    }

    stepValidationMessage.textContent = text;
    stepValidationMessage.className = `small mt-3 ${type ? `text-${type}` : ''}`;
  }

  function handleIngredientCardClick(event) {
    if (!(event.target instanceof Element)) {
      return;
    }

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
    updateSelectedCardClasses();
    updateIngredientCount();
    updatePriceOverview();
    updateSummary();
    updateVisualizer();
  }

  function readSelectionsFromUi() {
    const selectedSizeInput = sizeInputs.find((input) => input.checked);
    state.sizeId = selectedSizeInput ? Number(selectedSizeInput.value) : null;

    state.ingredientIds = ingredientCheckboxes
      .filter((checkbox) => checkbox.checked)
      .map((checkbox) => Number(checkbox.value));

    state.toppingIds = toppingCheckboxes
      .filter((checkbox) => checkbox.checked)
      .map((checkbox) => Number(checkbox.value));

    state.sweetness = sweetnessSelect ? sweetnessSelect.value : 'medium';
    state.consistency = consistencySelect ? consistencySelect.value : 'standard';
    state.temperature = temperatureSelect ? temperatureSelect.value : 'chilled';
  }

  function syncUiFromState() {
    sizeInputs.forEach((input) => {
      input.checked = Number(input.value) === Number(state.sizeId);
    });

    ingredientCheckboxes.forEach((checkbox) => {
      checkbox.checked = state.ingredientIds.includes(Number(checkbox.value));
    });

    toppingCheckboxes.forEach((checkbox) => {
      checkbox.checked = state.toppingIds.includes(Number(checkbox.value));
    });

    if (sweetnessSelect) {
      sweetnessSelect.value = state.sweetness;
    }

    if (consistencySelect) {
      consistencySelect.value = state.consistency;
    }

    if (temperatureSelect) {
      temperatureSelect.value = state.temperature;
    }
  }

  async function applyCoupon() {
    const code = (couponCodeInput ? couponCodeInput.value : '').trim().toUpperCase();

    if (!state.sizeId) {
      showCouponMessage('Bitte zuerst eine Größe wählen.', 'danger');
      return;
    }

    if (state.ingredientIds.length === 0) {
      showCouponMessage('Bitte zuerst Zutaten auswählen.', 'danger');
      return;
    }

    if (code === '') {
      state.couponCode = '';
      state.couponApplied = false;
      state.discountAmount = 0;
      showCouponMessage('Bitte einen Gutscheincode eingeben.', 'danger');
      refreshUi();
      return;
    }

    showCouponMessage('Gutschein wird geprüft...', 'muted');

    try {
      const response = await fetch(configData.api.applyCoupon, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': configData.csrfToken,
        },
        body: JSON.stringify({
          size_id: state.sizeId,
          ingredient_ids: state.ingredientIds,
          topping_ids: state.toppingIds,
          coupon_code: code,
          csrf_token: configData.csrfToken,
        }),
      });

      const data = await response.json();

      state.couponCode = code;
      state.couponApplied = Boolean(data.valid);
      state.discountAmount = Number(data.pricing && data.pricing.discount_amount ? data.pricing.discount_amount : 0);

      if (data.valid) {
        showCouponMessage(data.message || 'Gutschein wurde angewendet.', 'success');
      } else {
        state.discountAmount = 0;
        showCouponMessage(data.message || 'Gutschein konnte nicht angewendet werden.', 'danger');
      }

      refreshUi();
    } catch (error) {
      state.couponApplied = false;
      state.discountAmount = 0;
      showCouponMessage('Fehler bei der Gutscheinprüfung.', 'danger');
      refreshUi();
    }
  }

  async function saveConfiguration() {
    if (!state.sizeId) {
      showOrderMessage('Bitte zuerst eine Größe wählen.', 'danger');
      return;
    }

    if (state.ingredientIds.length === 0) {
      showOrderMessage('Bitte mindestens eine Zutat auswählen.', 'danger');
      return;
    }

    const configurationName = configurationNameInput ? configurationNameInput.value : 'Mein Smoothie';
    showOrderMessage('Konfiguration wird gespeichert...', 'muted');

    try {
      const response = await fetch(configData.api.saveConfiguration, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': configData.csrfToken,
        },
        body: JSON.stringify({
          name: configurationName,
          size_id: state.sizeId,
          ingredient_ids: state.ingredientIds,
          sweetness: state.sweetness,
          consistency: state.consistency,
          temperature: state.temperature,
          topping_ids: state.toppingIds,
          coupon_code: state.couponApplied ? state.couponCode : '',
          csrf_token: configData.csrfToken,
        }),
      });

      const data = await response.json();

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
    state.sweetness = preset.sweetness || 'medium';
    state.consistency = preset.consistency || 'standard';
    state.temperature = preset.temperature || 'chilled';

    markCouponDirty();
    syncUiFromState();
    refreshUi();
    showOrderMessage(`Preset "${preset.name}" wurde geladen.`, 'success');
  }

  sizeInputs.forEach((input) => {
    input.addEventListener('change', () => {
      readSelectionsFromUi();
      markCouponDirty();
      refreshUi();
    });
  });

  ingredientCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
      readSelectionsFromUi();
      markCouponDirty();
      refreshUi();
    });
  });

  ingredientCards.forEach((card) => {
    card.addEventListener('click', handleIngredientCardClick);
  });

  toppingCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
      readSelectionsFromUi();
      markCouponDirty();
      refreshUi();
    });
  });

  if (sweetnessSelect) {
    sweetnessSelect.addEventListener('change', () => {
      readSelectionsFromUi();
      refreshUi();
    });
  }

  if (consistencySelect) {
    consistencySelect.addEventListener('change', () => {
      readSelectionsFromUi();
      refreshUi();
    });
  }

  if (temperatureSelect) {
    temperatureSelect.addEventListener('change', () => {
      readSelectionsFromUi();
      refreshUi();
    });
  }

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

  readSelectionsFromUi();
  filterIngredients();
  setStep(1);
  refreshUi();
})();
