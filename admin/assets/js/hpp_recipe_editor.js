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
  var currency = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
  var preciseCurrency = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 6 });

  function amount(value) {
    var text = String(value == null ? '' : value).trim();
    if (!/^\d+(?:[.,]\d+)?$/.test(text)) return null;
    var parsed = Number(text.replace(',', '.'));
    return Number.isFinite(parsed) ? parsed : null;
  }

  function rupiah(value, precise) {
    var formatter = precise ? preciseCurrency : currency;
    return 'Rp' + formatter.format(value || 0);
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
    var unitLabel = row.querySelector('[data-unit-label]');
    var snapshotNote = row.querySelector('[data-snapshot-note]');
    var isMaster = select.value !== '';

    customGroup.hidden = isMaster;
    customInput.required = !isMaster;
    unitInput.readOnly = isMaster;
    if (isMaster) {
      unitLabel.textContent = 'per ' + selectedOption.dataset.unit;
      if (useCurrentCost) {
        unitInput.value = selectedOption.dataset.unitCost || '0';
      }
      var currentCost = Number(selectedOption.dataset.unitCost || 0);
      var snapshotCost = amount(unitInput.dataset.snapshotCost);
      snapshotNote.textContent = snapshotCost !== null && Math.abs(snapshotCost - currentCost) >= 0.0000005
        ? 'Snapshot ' + rupiah(snapshotCost, true) + ' · harga master ' + rupiah(currentCost, true)
        : '';
    } else {
      unitLabel.textContent = 'per satuan item';
      snapshotNote.textContent = '';
    }
  }

  function addLine(line) {
    if (linesContainer.children.length >= 40) return;
    var row = createRow(lineTemplate, nextLineIndex++);
    var source = line || {};
    row.querySelector('[data-line-id]').value = source.id || '';
    row.querySelector('[data-ingredient-select]').value = source.ingredient_id || '';
    row.querySelector('[data-custom-name]').value = source.custom_name || '';
    row.querySelector('[data-line-qty]').value = source.qty == null ? '' : source.qty;
    var unitInput = row.querySelector('[data-line-unit-cost]');
    unitInput.value = source.unit_cost == null ? '0' : source.unit_cost;
    unitInput.dataset.snapshotCost = unitInput.value;
    setLineMode(row, !source.id || !source.ingredient_id || refreshInput.checked);
    row.querySelector('[data-ingredient-select]').addEventListener('change', function () {
      setLineMode(row, true);
      var unitInput = row.querySelector('[data-line-unit-cost]');
      unitInput.dataset.snapshotCost = unitInput.value;
      calculate();
    });
    row.querySelector('[data-remove-line]').addEventListener('click', function () {
      row.remove();
      calculate();
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
    row.querySelector('[data-cost-amount]').value = source.amount == null ? '' : source.amount;
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
      var row = document.createElement('tr');
      [Number(markup) + '%', rupiah(price, false), rupiah(profit, true), margin.toFixed(2) + '%'].forEach(function (text) {
        var cell = document.createElement('td');
        cell.textContent = text;
        row.appendChild(cell);
      });
      var actionCell = document.createElement('td');
      if (finalPriceInput) {
        var usePriceButton = document.createElement('button');
        usePriceButton.type = 'button';
        usePriceButton.className = 'btn btn-cancel';
        usePriceButton.textContent = 'Gunakan';
        usePriceButton.dataset.useScenarioPrice = String(price);
        usePriceButton.addEventListener('click', function () {
          finalPriceInput.value = usePriceButton.dataset.useScenarioPrice;
        });
        actionCell.appendChild(usePriceButton);
      } else {
        actionCell.textContent = 'Simpan dahulu';
      }
      row.appendChild(actionCell);
      scenarios.appendChild(row);
    });
    status.textContent = 'Pratinjau mengikuti aturan kalkulasi server; simpan untuk menghitung ulang dan mencatat resep.';
  }

  form.querySelector('[data-add-line]').addEventListener('click', function () {
    addLine({ ingredient_id: '', custom_name: '', qty: '', unit_cost: '0' });
  });
  form.querySelector('[data-add-cost]').addEventListener('click', function () {
    addCost({ label: '', calc_type: 'pct_bahan', amount: '' });
  });
  refreshInput.addEventListener('change', function () {
    Array.prototype.forEach.call(linesContainer.querySelectorAll('[data-line-row]'), function (row) {
      var select = row.querySelector('[data-ingredient-select]');
      var unitInput = row.querySelector('[data-line-unit-cost]');
      if (select.value === '') return;
      if (refreshInput.checked) {
        unitInput.value = select.options[select.selectedIndex].dataset.unitCost || '0';
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

  (editorData.lines || []).forEach(addLine);
  (editorData.costs || []).forEach(addCost);
  calculate();
}());
