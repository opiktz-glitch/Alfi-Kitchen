(function () {
  'use strict';

  var form = document.getElementById('hpp-recipe-form');
  if (!form) return;

  var dataElement = document.getElementById('hpp-editor-data');
  var editorData = JSON.parse(dataElement.textContent || '{}');
  var linesContainer = form.querySelector('[data-lines]');
  var costsContainer = form.querySelector('[data-costs]');
  var lineTemplate = document.getElementById('hpp-line-template');
  var costTemplate = document.getElementById('hpp-cost-template');
  var nextLineIndex = 0;
  var nextCostIndex = 0;
  var refreshInput = form.querySelector('[data-refresh-snapshots]');
  var finalPriceInput = document.querySelector('[data-final-price]');
  var quickInput = document.getElementById('quick-ingredient-input');
  var quickAddBtn = document.getElementById('btn-quick-add');
  var datalist = document.getElementById('master-ingredients-datalist');
  var currency = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

  function formatCleanDecimal(value, maxDecimals) {
    if (value == null || value === '') return '';
    maxDecimals = maxDecimals === undefined ? 2 : maxDecimals;
    var n = Number(String(value).replace(',', '.'));
    if (!Number.isFinite(n)) return String(value);
    var rounded = Math.round(n * Math.pow(10, maxDecimals)) / Math.pow(10, maxDecimals);
    var str = rounded.toFixed(maxDecimals);
    return str.indexOf('.') !== -1 ? str.replace(/\.?0+$/, '') : str;
  }

  function amount(value) {
    var text = String(value == null ? '' : value).trim();
    if (!/^\d+(?:[.,]\d+)?$/.test(text)) return null;
    var parsed = Number(text.replace(',', '.'));
    return Number.isFinite(parsed) ? parsed : null;
  }

  function rupiah(value) {
    var n = Number(value || 0);
    var rounded = Math.ceil(n);
    return 'Rp ' + rounded.toLocaleString('id-ID');
  }

  function roundHalfUpTwo(value) {
    var scaled = value * 100;
    return Math.floor(scaled + Number.EPSILON * Math.max(1, Math.abs(scaled)) + 0.5) / 100;
  }

  function createRow(template, index) {
    var markup = template.innerHTML.replace(/__INDEX__/g, String(index));
    var holder = document.createElement('div');
    holder.innerHTML = markup.trim();
    return holder.firstElementChild;
  }

  function lineUnit(row, refresh) {
    var select = row.querySelector('[data-ingredient-select]');
    var selectedOption = select.options[select.selectedIndex];
    var unitInput = row.querySelector('[data-line-unit-cost]');
    if (select.value !== '') {
      if (refresh) return Number(selectedOption.dataset.unitCost || 0);
      return amount(unitInput.value);
    }
    return amount(unitInput.value);
  }

  function setLineMode(row, useCurrentCost) {
    var select = row.querySelector('[data-ingredient-select]');
    var selectedOption = select.options[select.selectedIndex];
    var customGroup = row.querySelector('[data-custom-group]');
    var customInput = row.querySelector('[data-custom-name]');
    var unitInput = row.querySelector('[data-line-unit-cost]');
    var customCostInput = row.querySelector('[data-custom-cost-input]');
    var isMaster = select.value !== '';

    if (customGroup) customGroup.hidden = isMaster;
    if (customInput) customInput.required = !isMaster;
    var unitBadge = row.querySelector('[data-unit-badge]');
    if (unitBadge) {
      unitBadge.textContent = isMaster ? (selectedOption.dataset.unit || 'satuan') : 'satuan';
    }
    if (isMaster) {
      if (useCurrentCost && selectedOption) {
        unitInput.value = formatCleanDecimal(selectedOption.dataset.unitCost || '0', 2);
      }
    } else {
      if (customCostInput && unitInput) {
        if (!customCostInput.value && unitInput.value && unitInput.value !== '0') {
          customCostInput.value = unitInput.value;
        }
      }
    }
  }

  function addLine(line) {
    if (linesContainer.children.length >= 40) return;
    var row = createRow(lineTemplate, nextLineIndex++);
    var source = line || {};
    var lineId = source.id || '';
    row.querySelector('[data-line-id]').value = lineId;
    row.querySelector('[data-ingredient-select]').value = source.ingredient_id || '';
    row.querySelector('[data-custom-name]').value = source.custom_name || '';
    row.querySelector('[data-line-qty]').value = source.qty == null || source.qty === '' ? '' : formatCleanDecimal(source.qty, 2);
    var unitInput = row.querySelector('[data-line-unit-cost]');
    unitInput.value = source.unit_cost == null || source.unit_cost === '' ? '0' : formatCleanDecimal(source.unit_cost, 2);
    unitInput.dataset.snapshotCost = lineId ? unitInput.value : '';

    var customCostInput = row.querySelector('[data-custom-cost-input]');
    if (customCostInput) {
      if (!source.ingredient_id && source.unit_cost && source.unit_cost !== '0') {
        customCostInput.value = formatCleanDecimal(source.unit_cost, 2);
      }
      customCostInput.addEventListener('input', function () {
        unitInput.value = customCostInput.value || '0';
        calculate();
      });
    }

    setLineMode(row, !lineId || !source.ingredient_id || (refreshInput && refreshInput.checked));
    row.querySelector('[data-ingredient-select]').addEventListener('change', function () {
      var selectedId = this.value;
      if (selectedId !== '') {
        var allRows = linesContainer.querySelectorAll('[data-line-row]');
        for (var i = 0; i < allRows.length; i++) {
          if (allRows[i] !== row) {
            var otherSelect = allRows[i].querySelector('[data-ingredient-select]');
            if (otherSelect && otherSelect.value === selectedId) {
              var opt = this.options[this.selectedIndex];
              var name = opt ? opt.textContent : 'Bahan ini';
              showWarningToast('Bahan "' + name + '" sudah ada dalam daftar resep!');
              highlightAndFocusRow(allRows[i]);
              this.value = '';
              setLineMode(row, true);
              calculate();
              return;
            }
          }
        }
      }
      var unitInput = row.querySelector('[data-line-unit-cost]');
      unitInput.dataset.snapshotCost = '';
      setLineMode(row, true);
      calculate();
    });
    row.querySelector('[data-remove-line]').addEventListener('click', function () {
      row.remove();
      calculate();
    });
    var qtyInput = row.querySelector('[data-line-qty]');
    qtyInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (quickInput) {
          quickInput.focus();
        } else {
          addLine();
          var rows = linesContainer.querySelectorAll('[data-line-row]');
          var last = rows[rows.length - 1];
          if (last) {
            var nextSelect = last.querySelector('[data-ingredient-select]');
            if (nextSelect) nextSelect.focus();
          }
        }
      }
    });
    row.addEventListener('input', calculate);
    linesContainer.appendChild(row);
  }

  function addCost(cost) {
    if (costsContainer.children.length >= 15) return;
    var row = createRow(costTemplate, nextCostIndex++);
    var source = cost || {};
    row.querySelector('[data-cost-label]').value = source.label || '';
    row.querySelector('[data-cost-type]').value = source.calc_type || 'pct_bahan';
    row.querySelector('[data-cost-amount]').value = source.amount == null || source.amount === '' ? '' : formatCleanDecimal(source.amount, 2);
    row.querySelector('[data-remove-cost]').addEventListener('click', function () {
      row.remove();
      calculate();
    });
    row.addEventListener('input', calculate);
    row.addEventListener('change', calculate);
    costsContainer.appendChild(row);
  }

  function getRecipeInputs() {
    var yieldValue = amount(form.elements.yield_jars.value);
    var stepValue = amount(form.elements.round_step.value);
    if (!yieldValue || Math.floor(yieldValue) !== yieldValue || yieldValue > 200) return null;
    if (!stepValue || Math.floor(stepValue) !== stepValue) return null;

    var materialCost = 0;
    var lineRows = Array.prototype.slice.call(linesContainer.querySelectorAll('[data-line-row]'));
    if (lineRows.length === 0 || lineRows.length > 40) return null;
    for (var i = 0; i < lineRows.length; i++) {
      var qty = amount(lineRows[i].querySelector('[data-line-qty]').value);
      var unitCost = lineUnit(lineRows[i], refreshInput.checked);
      if (qty === null || qty <= 0 || unitCost === null || unitCost < 0) return null;
      materialCost += qty * unitCost;
      if (!Number.isFinite(materialCost)) return null;
    }

    var operationalCost = 0;
    var costs = Array.prototype.slice.call(costsContainer.querySelectorAll('[data-cost-row]'));
    if (costs.length > 15) return null;
    for (var j = 0; j < costs.length; j++) {
      var type = costs[j].querySelector('[data-cost-type]').value;
      var value = amount(costs[j].querySelector('[data-cost-amount]').value);
      if (value === null || value < 0 || (type === 'pct_bahan' && value > 100)) return null;
      var lineCost = type === 'pct_bahan'
        ? materialCost * value / 100
        : (type === 'per_jar' ? value * yieldValue : value);
      if (!Number.isFinite(lineCost)) return null;
      operationalCost += lineCost;
      costs[j].querySelector('[data-cost-total]').textContent = rupiah(lineCost, true);
    }

    var total = materialCost + operationalCost;
    if (!Number.isFinite(total) || total >= 2147483647.5) return null;
    var roundedTotal = roundHalfUpTwo(total);
    var totalCents = Math.round(roundedTotal * 100);
    var stepCents = stepValue * 100;
    var hppRecipe = totalCents === 0 ? 0 : Math.ceil(totalCents / stepCents) * stepValue;
    if (!Number.isSafeInteger(hppRecipe) || hppRecipe > 2147483647) return null;

    return {
      materialCost: materialCost,
      operationalCost: operationalCost,
      total: total,
      hppRecipe: hppRecipe,
      hppPerJar: hppRecipe / yieldValue,
      yieldJars: yieldValue
    };
  }

  function calculate() {
    var result = getRecipeInputs();
    var status = form.querySelector('[data-calculation-status]');
    var scenarios = form.querySelector('[data-scenarios]');
    var lineRows = Array.prototype.slice.call(linesContainer.querySelectorAll('[data-line-row]'));

    if (result === null) {
      status.textContent = 'Lengkapi input dengan angka yang valid untuk melihat pratinjau.';
      scenarios.replaceChildren();
      lineRows.forEach(function (row) {
        row.querySelector('[data-line-total]').textContent = '—';
      });
      form.querySelector('[data-total-material]').textContent = '—';
      form.querySelector('[data-total-operational]').textContent = '—';
      form.querySelector('[data-total-actual]').textContent = '—';
      form.querySelector('[data-hpp-recipe]').textContent = '—';
      form.querySelector('[data-hpp-per-jar]').textContent = '—';
      return;
    }

    lineRows.forEach(function (row) {
      var qty = amount(row.querySelector('[data-line-qty]').value) || 0;
      var unitCost = lineUnit(row, refreshInput.checked) || 0;
      row.querySelector('[data-line-total]').textContent = rupiah(qty * unitCost, true);
    });
    form.querySelector('[data-total-material]').textContent = rupiah(result.materialCost, true);
    form.querySelector('[data-total-operational]').textContent = rupiah(result.operationalCost, true);
    form.querySelector('[data-total-actual]').textContent = rupiah(result.total, true);
    form.querySelector('[data-hpp-recipe]').textContent = rupiah(result.hppRecipe, false);
    form.querySelector('[data-hpp-per-jar]').textContent = rupiah(result.hppPerJar, true);

    scenarios.replaceChildren();
    editorData.margins.forEach(function (markup) {
      var theoreticalPrice = result.hppPerJar * (1 + Number(markup) / 100);
      var price = Math.ceil(roundHalfUpTwo(theoreticalPrice));
      var profit = price - result.hppPerJar;
      var margin = price > 0 ? profit / price * 100 : 0;
      var marginRounded = Math.round(margin * 100) / 100;
      var marginText = (marginRounded % 1 === 0 ? marginRounded.toFixed(0) : marginRounded.toFixed(2).replace(/\.?0+$/, '').replace('.', ',')) + '%';

      var item = document.createElement('div');
      item.className = 'hpp-scenario-item';

      var badge = document.createElement('div');
      badge.className = 'hpp-scenario-badge';
      badge.textContent = '+' + Number(markup) + '%';
      item.appendChild(badge);

      var info = document.createElement('div');
      info.className = 'hpp-scenario-info';
      var priceEl = document.createElement('span');
      priceEl.className = 'hpp-scenario-price';
      priceEl.textContent = rupiah(price);
      var metaEl = document.createElement('span');
      metaEl.className = 'hpp-scenario-meta';
      metaEl.textContent = 'Laba ' + rupiah(profit) + ' · Margin ' + marginText;
      info.appendChild(priceEl);
      info.appendChild(metaEl);
      item.appendChild(info);

      if (finalPriceInput) {
        var action = document.createElement('div');
        action.className = 'hpp-scenario-action';
        var usePriceButton = document.createElement('button');
        usePriceButton.type = 'button';
        usePriceButton.className = 'btn btn-cancel btn-sm';
        usePriceButton.textContent = 'Gunakan';
        usePriceButton.dataset.useScenarioPrice = String(price);
        usePriceButton.addEventListener('click', function () {
          finalPriceInput.value = usePriceButton.dataset.useScenarioPrice;
          switchTab('tab-pane-costs-pricing');
          finalPriceInput.focus();
        });
        action.appendChild(usePriceButton);
        item.appendChild(action);
      }

      scenarios.appendChild(item);
    });
    status.textContent = 'Pratinjau mengikuti aturan kalkulasi server; simpan untuk menghitung ulang dan mencatat resep.';
  }

  function showWarningToast(message) {
    var existingToast = document.querySelector('.toast.is-live-toast');
    if (existingToast) existingToast.remove();
    var toast = document.createElement('div');
    toast.className = 'toast toast-error is-live-toast';
    toast.setAttribute('role', 'alert');
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function () {
      toast.classList.add('toast-hide');
      setTimeout(function () { toast.remove(); }, 350);
    }, 3000);
  }

  function findRowByIngredientId(ingredientId) {
    var rows = linesContainer.querySelectorAll('[data-line-row]');
    for (var i = 0; i < rows.length; i++) {
      var sel = rows[i].querySelector('[data-ingredient-select]');
      if (sel && sel.value === String(ingredientId)) {
        return rows[i];
      }
    }
    return null;
  }

  function findRowByCustomName(name) {
    var lower = name.toLowerCase().trim();
    var rows = linesContainer.querySelectorAll('[data-line-row]');
    for (var i = 0; i < rows.length; i++) {
      var inp = rows[i].querySelector('[data-custom-name]');
      if (inp && inp.value && inp.value.toLowerCase().trim() === lower) {
        return rows[i];
      }
    }
    return null;
  }

  function highlightAndFocusRow(row) {
    row.classList.remove('is-duplicate-highlight');
    void row.offsetWidth;
    row.classList.add('is-duplicate-highlight');
    setTimeout(function () {
      row.classList.remove('is-duplicate-highlight');
    }, 1500);
    var qtyInput = row.querySelector('[data-line-qty]');
    if (qtyInput) {
      qtyInput.focus();
      qtyInput.select();
    }
    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function handleQuickAdd() {
    if (!quickInput) return;
    var query = quickInput.value.trim();
    if (!query) {
      addLine({ ingredient_id: '', custom_name: '', qty: '', unit_cost: '0' });
      var allRows = linesContainer.querySelectorAll('[data-line-row]');
      var lastRow = allRows[allRows.length - 1];
      if (lastRow) {
        var sel = lastRow.querySelector('[data-ingredient-select]');
        if (sel) sel.focus();
      }
      return;
    }

    var lower = query.toLowerCase();
    var matchedId = null;
    var matchedCost = null;
    var matchedName = query;
    if (datalist) {
      var options = datalist.querySelectorAll('option');
      for (var i = 0; i < options.length; i++) {
        var optVal = options[i].value.toLowerCase();
        if (optVal === lower || optVal.indexOf(lower) !== -1) {
          matchedId = options[i].dataset.id;
          matchedCost = options[i].dataset.unitCost;
          matchedName = options[i].value;
          break;
        }
      }
    }

    if (matchedId) {
      var existingRow = findRowByIngredientId(matchedId);
      if (existingRow) {
        showWarningToast('Bahan "' + matchedName + '" sudah ada dalam daftar resep!');
        highlightAndFocusRow(existingRow);
        quickInput.value = '';
        return;
      }
      addLine({ ingredient_id: matchedId, unit_cost: formatCleanDecimal(matchedCost, 2), qty: '' });
    } else {
      var existingCustom = findRowByCustomName(query);
      if (existingCustom) {
        showWarningToast('Item "' + query + '" sudah ada dalam daftar resep!');
        highlightAndFocusRow(existingCustom);
        quickInput.value = '';
        return;
      }
      addLine({ custom_name: query, ingredient_id: '', unit_cost: '0', qty: '' });
    }
    quickInput.value = '';
    calculate();
    var rowsAfter = linesContainer.querySelectorAll('[data-line-row]');
    var newRow = rowsAfter[rowsAfter.length - 1];
    if (newRow) {
      var targetQty = newRow.querySelector('[data-line-qty]');
      if (targetQty) targetQty.focus();
    }
  }

  if (quickAddBtn) {
    quickAddBtn.addEventListener('click', handleQuickAdd);
  }
  if (quickInput) {
    quickInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleQuickAdd();
      }
    });
  }

  var addLineBtn = form.querySelector('[data-add-line]');
  if (addLineBtn) {
    addLineBtn.addEventListener('click', function () {
      addLine({ ingredient_id: '', custom_name: '', qty: '', unit_cost: '0' });
    });
  }
  form.querySelector('[data-add-cost]').addEventListener('click', function () {
    addCost({ label: '', calc_type: 'pct_bahan', amount: '' });
  });
  refreshInput.addEventListener('change', function () {
    Array.prototype.forEach.call(linesContainer.querySelectorAll('[data-line-row]'), function (row) {
      var select = row.querySelector('[data-ingredient-select]');
      var unitInput = row.querySelector('[data-line-unit-cost]');
      if (select.value === '') return;
      if (refreshInput.checked) {
        unitInput.value = formatCleanDecimal(select.options[select.selectedIndex].dataset.unitCost || '0', 2);
      } else {
        unitInput.value = unitInput.dataset.snapshotCost || unitInput.value;
      }
    });
    calculate();
  });
  form.addEventListener('input', calculate);
  form.addEventListener('change', calculate);

  if (finalPriceInput) {
    Array.prototype.forEach.call(document.querySelectorAll('[data-round-price]'), function (button) {
      button.addEventListener('click', function () {
        var currentPrice = amount(finalPriceInput.value);
        var step = Number(button.dataset.roundPrice);
        if (currentPrice === null || currentPrice <= 0 || !Number.isInteger(step) || step <= 0) return;
        finalPriceInput.value = String(Math.ceil(currentPrice / step) * step);
      });
    });
  }

  function switchTab(targetId) {
    if (!targetId) return;
    var buttons = document.querySelectorAll('[data-tab-target]');
    var panes = document.querySelectorAll('[data-tab-pane]');
    Array.prototype.forEach.call(buttons, function (btn) {
      var isActive = btn.getAttribute('data-tab-target') === targetId;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    Array.prototype.forEach.call(panes, function (pane) {
      var isTarget = pane.id === targetId;
      if (isTarget) {
        pane.removeAttribute('hidden');
        pane.style.display = 'block';
      } else {
        pane.setAttribute('hidden', '');
        pane.style.display = 'none';
      }
    });
    var pricingSections = document.querySelectorAll('[data-pricing-section]');
    var showPricing = targetId === 'tab-pane-costs-pricing';
    Array.prototype.forEach.call(pricingSections, function (sec) {
      if (showPricing) {
        sec.removeAttribute('hidden');
        sec.style.display = 'block';
      } else {
        sec.setAttribute('hidden', '');
        sec.style.display = 'none';
      }
    });
  }

  document.addEventListener('click', function (e) {
    var tabBtn = e.target.closest('[data-tab-target]');
    if (tabBtn) {
      e.preventDefault();
      switchTab(tabBtn.getAttribute('data-tab-target'));
      return;
    }
    var nextBtn = e.target.closest('#btn-next-to-costs');
    if (nextBtn) {
      e.preventDefault();
      switchTab('tab-pane-costs-pricing');
      return;
    }
    var backBtn = e.target.closest('#btn-back-to-materials');
    if (backBtn) {
      e.preventDefault();
      switchTab('tab-pane-materials');
      return;
    }
    var toggleBtn = e.target.closest('#btn-toggle-recipe-details');
    if (toggleBtn) {
      e.preventDefault();
      var extra = document.getElementById('recipe-extra-details');
      if (extra) {
        var isHidden = extra.hasAttribute('hidden');
        if (isHidden) {
          extra.removeAttribute('hidden');
          toggleBtn.setAttribute('aria-expanded', 'true');
          toggleBtn.classList.add('is-active');
        } else {
          extra.setAttribute('hidden', '');
          toggleBtn.setAttribute('aria-expanded', 'false');
          toggleBtn.classList.remove('is-active');
        }
      }
      return;
    }
  });

  (editorData.lines || []).forEach(addLine);
  (editorData.costs || []).forEach(addCost);
  calculate();
}());
