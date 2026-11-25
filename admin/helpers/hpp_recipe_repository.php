<?php
require_once __DIR__ . '/hpp_calc.php';

if (!function_exists('hpp_recipe_text')) {
    function hpp_recipe_text($value, string $field, int $maxLength, bool $required): string
    {
        if (!is_string($value)) {
            if ($value === null && !$required) {
                return '';
            }
            throw new InvalidArgumentException("$field harus berupa teks.");
        }

        $text = trim($value);
        $length = preg_match_all('/./us', $text, $matches);
        $length = $length === false ? strlen($text) : $length;
        if (($required && $text === '') || $length > $maxLength) {
            throw new InvalidArgumentException("$field wajib diisi dan maksimal $maxLength karakter.");
        }

        return $text;
    }
}

if (!function_exists('hpp_recipe_decimal')) {
    function hpp_recipe_decimal($value, string $field, int $scale, float $minimum, float $maximum, bool $allowZero = true): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException("$field harus berupa angka.");
        }

        $text = trim((string) $value);
        $pattern = '/^\d+(?:[.,]\d{1,' . $scale . '})?$/';
        if (!preg_match($pattern, $text)) {
            throw new InvalidArgumentException("$field harus berupa angka dengan maksimal $scale angka desimal.");
        }

        $number = hpp_parse_number($text, $field);
        if (
            $number < $minimum
            || $number > $maximum
            || (!$allowZero && $number <= 0)
        ) {
            throw new InvalidArgumentException("$field berada di luar batas yang diizinkan.");
        }

        return str_replace(',', '.', $text);
    }
}

if (!function_exists('hpp_recipe_positive_id')) {
    function hpp_recipe_positive_id($value, string $field, bool $optional = false): ?int
    {
        if ($optional && ($value === null || $value === '')) {
            return null;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new InvalidArgumentException("$field tidak valid.");
        }
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            throw new InvalidArgumentException("$field tidak valid.");
        }

        return $id;
    }
}

if (!function_exists('hpp_normalize_recipe_payload')) {
    function hpp_normalize_recipe_payload(array $input, array $existingLines, PDO $pdo, bool $refreshMasterSnapshots): array
    {
        $name = hpp_recipe_text($input['name'] ?? null, 'Nama resep', 120, true);
        $variant = hpp_recipe_text($input['variant'] ?? '', 'Varian resep', 40, false);
        $note = hpp_recipe_text($input['note'] ?? '', 'Catatan resep', 255, false);

        $yieldJars = hpp_parse_positive_integer($input['yield_jars'] ?? null, 'Jumlah kemasan');
        if ($yieldJars > 200) {
            throw new InvalidArgumentException('Jumlah kemasan harus antara 1 dan 200.');
        }
        $roundStep = hpp_parse_positive_integer($input['round_step'] ?? null, 'Langkah pembulatan');
        if ($roundStep > 2147483647) {
            throw new InvalidArgumentException('Langkah pembulatan melebihi batas database.');
        }

        $jarMlInput = $input['jar_ml'] ?? '';
        if ($jarMlInput === '') {
            $jarMl = null;
        } else {
            $jarMl = hpp_parse_positive_integer($jarMlInput, 'Ukuran kemasan');
            if ($jarMl > 32767) {
                throw new InvalidArgumentException('Ukuran kemasan harus tidak melebihi 32.767 ml/g.');
            }
        }

        $rawLines = $input['lines'] ?? null;
        if (!is_array($rawLines) || count($rawLines) < 1) {
            throw new InvalidArgumentException('Resep harus memiliki minimal satu baris bahan.');
        }
        if (count($rawLines) > 40) {
            throw new InvalidArgumentException('Maksimal 40 baris bahan per resep.');
        }

        $ingredientQuery = $pdo->prepare(
            'SELECT id, pack_size, pack_price FROM hpp_ingredients WHERE id = ?'
        );
        $normalizedLines = [];
        $seenLineIds = [];
        foreach (array_values($rawLines) as $index => $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException('Format baris bahan tidak valid.');
            }

            $lineId = hpp_recipe_positive_id($line['id'] ?? null, 'ID baris bahan', true);
            if ($lineId !== null) {
                if (isset($seenLineIds[$lineId]) || !isset($existingLines[$lineId])) {
                    throw new InvalidArgumentException('Baris bahan tidak ditemukan pada resep ini.');
                }
                $seenLineIds[$lineId] = true;
            }

            $quantity = hpp_recipe_decimal(
                $line['qty'] ?? null,
                'Jumlah bahan',
                3,
                0,
                999999999.999,
                false
            );
            $ingredientId = hpp_recipe_positive_id($line['ingredient_id'] ?? null, 'Bahan master', true);

            if ($ingredientId !== null) {
                $ingredientQuery->execute([$ingredientId]);
                $ingredient = $ingredientQuery->fetch(PDO::FETCH_ASSOC);
                if (!$ingredient) {
                    throw new InvalidArgumentException('Bahan master tidak ditemukan. Muat ulang halaman dan pilih bahan yang tersedia.');
                }

                $existingLine = $lineId !== null ? $existingLines[$lineId] : null;
                $sameIngredient = $existingLine !== null
                    && (int) ($existingLine['ingredient_id'] ?? 0) === $ingredientId;
                $unitCost = $sameIngredient && !$refreshMasterSnapshots
                    ? (float) $existingLine['unit_cost']
                    : hpp_unit_cost($ingredient['pack_price'], $ingredient['pack_size']);
                if ($unitCost > 99999999.999999) {
                    throw new InvalidArgumentException('Harga satuan bahan melebihi batas penyimpanan snapshot.');
                }

                $normalizedLines[] = [
                    'id' => $lineId,
                    'ingredient_id' => $ingredientId,
                    'custom_name' => null,
                    'qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'sort_order' => $index,
                ];
            } else {
                $customName = hpp_recipe_text($line['custom_name'] ?? null, 'Nama item bebas', 120, true);
                $unitCost = (float) hpp_recipe_decimal(
                    $line['unit_cost'] ?? null,
                    'Biaya per satuan item bebas',
                    6,
                    0,
                    99999999.999999
                );

                $normalizedLines[] = [
                    'id' => $lineId,
                    'ingredient_id' => null,
                    'custom_name' => $customName,
                    'qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'sort_order' => $index,
                ];
            }
        }

        $rawCosts = $input['costs'] ?? [];
        if (!is_array($rawCosts) || count($rawCosts) > 15) {
            throw new InvalidArgumentException('Maksimal 15 baris biaya operasional per resep.');
        }

        $normalizedCosts = [];
        foreach (array_values($rawCosts) as $index => $cost) {
            if (!is_array($cost)) {
                throw new InvalidArgumentException('Format biaya operasional tidak valid.');
            }

            $label = hpp_recipe_text($cost['label'] ?? null, 'Label biaya operasional', 80, true);
            $calcType = is_string($cost['calc_type'] ?? null) ? $cost['calc_type'] : '';
            if (!in_array($calcType, ['pct_bahan', 'per_jar', 'flat'], true)) {
                throw new InvalidArgumentException('Jenis biaya operasional tidak valid.');
            }

            $amount = (float) hpp_recipe_decimal(
                $cost['amount'] ?? null,
                'Nilai biaya operasional',
                4,
                0,
                99999999.9999
            );
            if ($calcType === 'pct_bahan' && $amount > 100) {
                throw new InvalidArgumentException('Persentase biaya operasional harus antara 0 dan 100.');
            }

            $normalizedCosts[] = [
                'label' => $label,
                'calc_type' => $calcType,
                'amount' => $amount,
                'sort_order' => $index,
            ];
        }

        $calculationLines = array_map(
            static function (array $line): array {
                return ['qty' => $line['qty'], 'unit_cost' => $line['unit_cost']];
            },
            $normalizedLines
        );
        $calculationCosts = array_map(
            static function (array $cost): array {
                return ['calc_type' => $cost['calc_type'], 'amount' => $cost['amount']];
            },
            $normalizedCosts
        );
        $calculation = hpp_calculate(
            $calculationLines,
            $calculationCosts,
            ['yield_jars' => $yieldJars, 'round_step' => $roundStep]
        );
        $totalCostCache = (int) round($calculation['total'], 0, PHP_ROUND_HALF_UP);
        $hppPerJarCache = (int) round($calculation['hpp_per_jar'], 0, PHP_ROUND_HALF_UP);

        if (
            $totalCostCache > 2147483647
            || $calculation['hpp_recipe'] > 2147483647
            || $hppPerJarCache > 2147483647
        ) {
            throw new InvalidArgumentException('Ringkasan biaya melebihi kapasitas penyimpanan.');
        }

        return [
            'recipe' => [
                'name' => $name,
                'variant' => $variant,
                'yield_jars' => $yieldJars,
                'jar_ml' => $jarMl,
                'round_step' => $roundStep,
                'note' => $note !== '' ? $note : null,
                'total_cost' => $totalCostCache,
                'hpp_recipe' => $calculation['hpp_recipe'],
                'hpp_per_jar' => $hppPerJarCache,
            ],
            'lines' => $normalizedLines,
            'costs' => $normalizedCosts,
            'calculation' => $calculation,
        ];
    }
}

/**
 * Simpan resep beserta seluruh barisnya secara atomik.
 *
 * Baris master yang sudah tersimpan mempertahankan snapshot unit_cost sampai
 * $refreshMasterSnapshots diminta oleh aksi admin yang eksplisit.
 *
 * @return array{id: int, calculation: array<string, mixed>}
 */
if (!function_exists('hpp_save_recipe')) {
    function hpp_save_recipe(PDO $pdo, array $input, ?int $recipeId = null, bool $refreshMasterSnapshots = false): array
    {
        if ($recipeId !== null && $recipeId < 1) {
            throw new InvalidArgumentException('ID resep tidak valid.');
        }
        if ($pdo->inTransaction()) {
            throw new LogicException('Penyimpanan resep harus dimulai di luar transaksi lain.');
        }

        $pdo->beginTransaction();
        try {
            $existingRecipe = null;
            $existingLines = [];
            if ($recipeId !== null) {
                $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
                $recipeStmt = $pdo->prepare('SELECT id FROM hpp_recipes WHERE id = ?' . $lockClause);
                $recipeStmt->execute([$recipeId]);
                $existingRecipe = $recipeStmt->fetch(PDO::FETCH_ASSOC);
                if (!$existingRecipe) {
                    throw new InvalidArgumentException('Resep tidak ditemukan atau sudah dihapus.');
                }

                $lineStmt = $pdo->prepare(
                    'SELECT id, ingredient_id, unit_cost FROM hpp_recipe_lines WHERE recipe_id = ?'
                );
                $lineStmt->execute([$recipeId]);
                foreach ($lineStmt->fetchAll(PDO::FETCH_ASSOC) as $line) {
                    $existingLines[(int) $line['id']] = $line;
                }
            }

            $normalized = hpp_normalize_recipe_payload($input, $existingLines, $pdo, $refreshMasterSnapshots);
            $recipe = $normalized['recipe'];

            if ($recipeId === null) {
                $insertRecipe = $pdo->prepare(
                    'INSERT INTO hpp_recipes '
                    . '(name, variant, yield_jars, jar_ml, round_step, note, total_cost, hpp_recipe, hpp_per_jar) '
                    . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertRecipe->execute([
                    $recipe['name'],
                    $recipe['variant'],
                    $recipe['yield_jars'],
                    $recipe['jar_ml'],
                    $recipe['round_step'],
                    $recipe['note'],
                    $recipe['total_cost'],
                    $recipe['hpp_recipe'],
                    $recipe['hpp_per_jar'],
                ]);
                $recipeId = (int) $pdo->lastInsertId();
            } else {
                $updateRecipe = $pdo->prepare(
                    'UPDATE hpp_recipes '
                    . 'SET name = ?, variant = ?, yield_jars = ?, jar_ml = ?, round_step = ?, note = ?, '
                    . 'total_cost = ?, hpp_recipe = ?, hpp_per_jar = ? WHERE id = ?'
                );
                $updateRecipe->execute([
                    $recipe['name'],
                    $recipe['variant'],
                    $recipe['yield_jars'],
                    $recipe['jar_ml'],
                    $recipe['round_step'],
                    $recipe['note'],
                    $recipe['total_cost'],
                    $recipe['hpp_recipe'],
                    $recipe['hpp_per_jar'],
                    $recipeId,
                ]);

                $pdo->prepare('DELETE FROM hpp_recipe_costs WHERE recipe_id = ?')->execute([$recipeId]);
            }

            $insertLine = $pdo->prepare(
                'INSERT INTO hpp_recipe_lines '
                . '(recipe_id, ingredient_id, custom_name, qty, unit_cost, sort_order) '
                . 'VALUES (?, ?, ?, ?, ?, ?)'
            );
            $updateLine = $pdo->prepare(
                'UPDATE hpp_recipe_lines '
                . 'SET ingredient_id = ?, custom_name = ?, qty = ?, unit_cost = ?, sort_order = ? '
                . 'WHERE id = ? AND recipe_id = ?'
            );
            $retainedLineIds = [];
            foreach ($normalized['lines'] as $line) {
                $lineValues = [
                    $line['ingredient_id'],
                    $line['custom_name'],
                    $line['qty'],
                    number_format($line['unit_cost'], 6, '.', ''),
                    $line['sort_order'],
                ];
                if ($line['id'] !== null) {
                    $updateLine->execute(array_merge($lineValues, [$line['id'], $recipeId]));
                    $retainedLineIds[$line['id']] = true;
                } else {
                    $insertLine->execute(array_merge([$recipeId], $lineValues));
                }
            }

            if ($existingRecipe !== null) {
                $deleteLine = $pdo->prepare('DELETE FROM hpp_recipe_lines WHERE id = ? AND recipe_id = ?');
                foreach (array_keys($existingLines) as $existingLineId) {
                    if (!isset($retainedLineIds[$existingLineId])) {
                        $deleteLine->execute([$existingLineId, $recipeId]);
                    }
                }
            }

            $insertCost = $pdo->prepare(
                'INSERT INTO hpp_recipe_costs (recipe_id, label, calc_type, amount, sort_order) '
                . 'VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($normalized['costs'] as $cost) {
                $insertCost->execute([
                    $recipeId,
                    $cost['label'],
                    $cost['calc_type'],
                    number_format($cost['amount'], 4, '.', ''),
                    $cost['sort_order'],
                ]);
            }

            $pdo->commit();
            return [
                'id' => $recipeId,
                'calculation' => $normalized['calculation'],
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

if (!function_exists('hpp_get_recipe')) {
    function hpp_get_recipe(PDO $pdo, int $recipeId): ?array
    {
        if ($recipeId < 1) {
            throw new InvalidArgumentException('ID resep tidak valid.');
        }

        $recipeStmt = $pdo->prepare('SELECT * FROM hpp_recipes WHERE id = ?');
        $recipeStmt->execute([$recipeId]);
        $recipe = $recipeStmt->fetch(PDO::FETCH_ASSOC);
        if (!$recipe) {
            return null;
        }

        $lineStmt = $pdo->prepare(
            'SELECT l.id, l.ingredient_id, l.custom_name, l.qty, l.unit_cost, l.sort_order, '
            . 'i.name AS ingredient_name, i.base_unit, i.pack_size, i.pack_price '
            . 'FROM hpp_recipe_lines l '
            . 'LEFT JOIN hpp_ingredients i ON i.id = l.ingredient_id '
            . 'WHERE l.recipe_id = ? ORDER BY l.sort_order, l.id'
        );
        $lineStmt->execute([$recipeId]);
        $recipe['lines'] = $lineStmt->fetchAll(PDO::FETCH_ASSOC);

        $costStmt = $pdo->prepare(
            'SELECT id, label, calc_type, amount, sort_order '
            . 'FROM hpp_recipe_costs WHERE recipe_id = ? ORDER BY sort_order, id'
        );
        $costStmt->execute([$recipeId]);
        $recipe['costs'] = $costStmt->fetchAll(PDO::FETCH_ASSOC);

        return $recipe;
    }
}

if (!function_exists('hpp_get_recipe_list')) {
    function hpp_get_recipe_list(PDO $pdo): array
    {
        $recipes = $pdo->query(
            'SELECT r.id, r.name, r.variant, r.yield_jars, r.hpp_recipe, r.hpp_per_jar, '
            . 'r.product_item_id, r.updated_at, p.name AS product_name, pi.name AS item_name, '
            . 'pi.price AS public_price '
            . 'FROM hpp_recipes r '
            . 'LEFT JOIN product_items pi ON pi.id = r.product_item_id '
            . 'LEFT JOIN products p ON p.id = pi.product_id '
            . 'ORDER BY r.updated_at DESC, r.id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recipes as &$recipe) {
            $recipe['ingredient_prices_changed'] = false;
        }
        unset($recipe);

        if ($recipes === []) {
            return $recipes;
        }

        $recipeIndexes = [];
        foreach ($recipes as $index => $recipe) {
            $recipeIndexes[(int) $recipe['id']] = $index;
        }
        $lines = $pdo->query(
            'SELECT l.recipe_id, l.unit_cost, i.pack_size, i.pack_price '
            . 'FROM hpp_recipe_lines l '
            . 'INNER JOIN hpp_ingredients i ON i.id = l.ingredient_id '
            . 'WHERE l.ingredient_id IS NOT NULL'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lines as $line) {
            $recipeId = (int) $line['recipe_id'];
            if (!isset($recipeIndexes[$recipeId])) {
                continue;
            }
            $currentUnitCost = hpp_unit_cost($line['pack_price'], $line['pack_size']);
            if (abs($currentUnitCost - (float) $line['unit_cost']) >= 0.0000005) {
                $recipes[$recipeIndexes[$recipeId]]['ingredient_prices_changed'] = true;
            }
        }

        return $recipes;
    }
}

if (!function_exists('hpp_duplicate_recipe')) {
    function hpp_duplicate_recipe(PDO $pdo, int $recipeId): int
    {
        if ($recipeId < 1) {
            throw new InvalidArgumentException('ID resep tidak valid.');
        }
        if ($pdo->inTransaction()) {
            throw new LogicException('Duplikasi resep harus dimulai di luar transaksi lain.');
        }

        $pdo->beginTransaction();
        try {
            $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $recipeStmt = $pdo->prepare(
                'SELECT name, variant, yield_jars, jar_ml, round_step, note, total_cost, hpp_recipe, hpp_per_jar '
                . 'FROM hpp_recipes WHERE id = ?' . $lockClause
            );
            $recipeStmt->execute([$recipeId]);
            $recipe = $recipeStmt->fetch(PDO::FETCH_ASSOC);
            if (!$recipe) {
                throw new InvalidArgumentException('Resep tidak ditemukan atau sudah dihapus.');
            }

            $insertRecipe = $pdo->prepare(
                'INSERT INTO hpp_recipes '
                . '(name, variant, yield_jars, jar_ml, round_step, note, product_item_id, total_cost, hpp_recipe, hpp_per_jar) '
                . 'VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?)'
            );
            $insertRecipe->execute([
                $recipe['name'],
                $recipe['variant'],
                $recipe['yield_jars'],
                $recipe['jar_ml'],
                $recipe['round_step'],
                $recipe['note'],
                $recipe['total_cost'],
                $recipe['hpp_recipe'],
                $recipe['hpp_per_jar'],
            ]);
            $duplicateId = (int) $pdo->lastInsertId();

            $lineStmt = $pdo->prepare(
                'SELECT ingredient_id, custom_name, qty, unit_cost, sort_order '
                . 'FROM hpp_recipe_lines WHERE recipe_id = ? ORDER BY sort_order, id'
            );
            $lineStmt->execute([$recipeId]);
            $insertLine = $pdo->prepare(
                'INSERT INTO hpp_recipe_lines '
                . '(recipe_id, ingredient_id, custom_name, qty, unit_cost, sort_order) '
                . 'VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($lineStmt->fetchAll(PDO::FETCH_ASSOC) as $line) {
                $insertLine->execute([
                    $duplicateId,
                    $line['ingredient_id'],
                    $line['custom_name'],
                    $line['qty'],
                    $line['unit_cost'],
                    $line['sort_order'],
                ]);
            }

            $costStmt = $pdo->prepare(
                'SELECT label, calc_type, amount, sort_order '
                . 'FROM hpp_recipe_costs WHERE recipe_id = ? ORDER BY sort_order, id'
            );
            $costStmt->execute([$recipeId]);
            $insertCost = $pdo->prepare(
                'INSERT INTO hpp_recipe_costs (recipe_id, label, calc_type, amount, sort_order) '
                . 'VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($costStmt->fetchAll(PDO::FETCH_ASSOC) as $cost) {
                $insertCost->execute([
                    $duplicateId,
                    $cost['label'],
                    $cost['calc_type'],
                    $cost['amount'],
                    $cost['sort_order'],
                ]);
            }

            $pdo->commit();
            return $duplicateId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

if (!function_exists('hpp_delete_recipe')) {
    function hpp_delete_recipe(PDO $pdo, int $recipeId): array
    {
        if ($recipeId < 1) {
            throw new InvalidArgumentException('ID resep tidak valid.');
        }
        if ($pdo->inTransaction()) {
            throw new LogicException('Penghapusan resep harus dimulai di luar transaksi lain.');
        }

        $pdo->beginTransaction();
        try {
            $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $recipeStmt = $pdo->prepare(
                'SELECT name, variant, product_item_id FROM hpp_recipes WHERE id = ?' . $lockClause
            );
            $recipeStmt->execute([$recipeId]);
            $recipe = $recipeStmt->fetch(PDO::FETCH_ASSOC);
            if (!$recipe) {
                throw new InvalidArgumentException('Resep tidak ditemukan atau sudah dihapus.');
            }

            $deleteStmt = $pdo->prepare('DELETE FROM hpp_recipes WHERE id = ?');
            $deleteStmt->execute([$recipeId]);
            $pdo->commit();

            return [
                'name' => (string) $recipe['name'],
                'variant' => (string) $recipe['variant'],
                'product_item_id' => $recipe['product_item_id'] === null
                    ? null
                    : (int) $recipe['product_item_id'],
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

if (!function_exists('hpp_recipe_has_changed_ingredient_prices')) {
    function hpp_recipe_has_changed_ingredient_prices(PDO $pdo, int $recipeId): bool
    {
        if ($recipeId < 1) {
            throw new InvalidArgumentException('ID resep tidak valid.');
        }
        $existsStmt = $pdo->prepare('SELECT 1 FROM hpp_recipes WHERE id = ?');
        $existsStmt->execute([$recipeId]);
        if ($existsStmt->fetchColumn() === false) {
            throw new InvalidArgumentException('Resep tidak ditemukan atau sudah dihapus.');
        }

        $linesStmt = $pdo->prepare(
            'SELECT l.unit_cost, i.pack_size, i.pack_price '
            . 'FROM hpp_recipe_lines l '
            . 'INNER JOIN hpp_ingredients i ON i.id = l.ingredient_id '
            . 'WHERE l.recipe_id = ? AND l.ingredient_id IS NOT NULL'
        );
        $linesStmt->execute([$recipeId]);
        foreach ($linesStmt->fetchAll(PDO::FETCH_ASSOC) as $line) {
            $currentUnitCost = hpp_unit_cost(
                $line['pack_price'],
                $line['pack_size']
            );
            if (abs($currentUnitCost - (float) $line['unit_cost']) >= 0.0000005) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('hpp_public_price_input')) {
    function hpp_public_price_input($value): int
    {
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException('Harga jual harus berupa bilangan bulat rupiah.');
        }

        $text = trim((string) $value);
        if (!preg_match('/^\d{1,8}$/', $text)) {
            throw new InvalidArgumentException('Harga jual harus berupa bilangan bulat antara Rp1 dan Rp99.999.999.');
        }
        $price = (int) $text;
        if ($price < 1) {
            throw new InvalidArgumentException('Harga jual harus lebih besar dari nol.');
        }

        return $price;
    }
}

if (!function_exists('hpp_public_price_cents')) {
    function hpp_public_price_cents($value, string $field): int
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException("$field tidak valid.");
        }

        $text = trim((string) $value);
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $text)) {
            throw new InvalidArgumentException("$field tidak valid.");
        }
        $parts = explode('.', $text, 2);
        $fraction = str_pad($parts[1] ?? '', 2, '0');

        return ((int) $parts[0] * 100) + (int) $fraction;
    }
}

if (!function_exists('hpp_get_public_price_preview')) {
    function hpp_get_public_price_preview(PDO $pdo, int $recipeId, $productItemId, $newPrice): array
    {
        if ($recipeId < 1) {
            throw new InvalidArgumentException('ID resep tidak valid.');
        }
        $itemId = hpp_recipe_positive_id($productItemId, 'Varian produk');
        $price = hpp_public_price_input($newPrice);

        $recipeStmt = $pdo->prepare(
            'SELECT id, name, yield_jars, hpp_recipe, product_item_id FROM hpp_recipes WHERE id = ?'
        );
        $recipeStmt->execute([$recipeId]);
        $recipe = $recipeStmt->fetch(PDO::FETCH_ASSOC);
        if (!$recipe) {
            throw new InvalidArgumentException('Resep tidak ditemukan atau sudah dihapus.');
        }

        $itemStmt = $pdo->prepare(
            'SELECT pi.id, pi.name AS item_name, pi.price, p.name AS product_name '
            . 'FROM product_items pi INNER JOIN products p ON p.id = pi.product_id WHERE pi.id = ?'
        );
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            throw new InvalidArgumentException('Varian produk tidak ditemukan atau sudah dihapus.');
        }

        $linkedRecipeStmt = $pdo->prepare(
            'SELECT id FROM hpp_recipes WHERE product_item_id = ? AND id <> ?'
        );
        $linkedRecipeStmt->execute([$itemId, $recipeId]);
        if ($linkedRecipeStmt->fetchColumn() !== false) {
            throw new InvalidArgumentException('Varian tersebut sudah terhubung ke resep lain.');
        }

        $yieldJars = (int) $recipe['yield_jars'];
        if ($yieldJars < 1) {
            throw new InvalidArgumentException('Jumlah hasil resep tidak valid.');
        }
        $hppPerJar = (int) $recipe['hpp_recipe'] / $yieldJars;

        return [
            'recipe_id' => (int) $recipe['id'],
            'recipe_name' => (string) $recipe['name'],
            'product_item_id' => $itemId,
            'product_name' => (string) $item['product_name'],
            'item_name' => (string) $item['item_name'],
            'old_price' => (string) $item['price'],
            'new_price' => $price,
            'hpp_per_jar' => $hppPerJar,
            'below_hpp' => $price < $hppPerJar,
        ];
    }
}

if (!function_exists('hpp_apply_public_price')) {
    function hpp_apply_public_price(
        PDO $pdo,
        int $recipeId,
        $productItemId,
        $newPrice,
        $expectedOldPrice
    ): array {
        if ($recipeId < 1) {
            throw new InvalidArgumentException('ID resep tidak valid.');
        }
        $itemId = hpp_recipe_positive_id($productItemId, 'Varian produk');
        $price = hpp_public_price_input($newPrice);
        $expectedOldPriceCents = hpp_public_price_cents($expectedOldPrice, 'Harga lama');
        if ($pdo->inTransaction()) {
            throw new LogicException('Penerapan harga harus dimulai di luar transaksi lain.');
        }

        $pdo->beginTransaction();
        try {
            $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $recipeStmt = $pdo->prepare(
                'SELECT id, name, yield_jars, hpp_recipe FROM hpp_recipes WHERE id = ?' . $lockClause
            );
            $recipeStmt->execute([$recipeId]);
            $recipe = $recipeStmt->fetch(PDO::FETCH_ASSOC);
            if (!$recipe) {
                throw new InvalidArgumentException('Resep tidak ditemukan atau sudah dihapus.');
            }

            $itemStmt = $pdo->prepare(
                'SELECT pi.id, pi.name AS item_name, pi.price, p.name AS product_name '
                . 'FROM product_items pi INNER JOIN products p ON p.id = pi.product_id '
                . 'WHERE pi.id = ?' . $lockClause
            );
            $itemStmt->execute([$itemId]);
            $item = $itemStmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                throw new InvalidArgumentException('Varian produk tidak ditemukan atau sudah dihapus.');
            }
            if (hpp_public_price_cents($item['price'], 'Harga lama') !== $expectedOldPriceCents) {
                throw new InvalidArgumentException('Harga varian berubah sejak pratinjau. Tinjau harga terbaru sebelum melanjutkan.');
            }

            $linkedRecipeStmt = $pdo->prepare(
                'SELECT id FROM hpp_recipes WHERE product_item_id = ? AND id <> ?' . $lockClause
            );
            $linkedRecipeStmt->execute([$itemId, $recipeId]);
            if ($linkedRecipeStmt->fetchColumn() !== false) {
                throw new InvalidArgumentException('Varian tersebut sudah terhubung ke resep lain.');
            }

            $yieldJars = (int) $recipe['yield_jars'];
            if ($yieldJars < 1) {
                throw new InvalidArgumentException('Jumlah hasil resep tidak valid.');
            }
            $hppPerJar = (int) $recipe['hpp_recipe'] / $yieldJars;

            $updatePrice = $pdo->prepare('UPDATE product_items SET price = ? WHERE id = ?');
            $updatePrice->execute([$price, $itemId]);
            $updateRecipe = $pdo->prepare('UPDATE hpp_recipes SET product_item_id = ? WHERE id = ?');
            $updateRecipe->execute([$itemId, $recipeId]);
            $pdo->commit();

            return [
                'recipe_id' => (int) $recipe['id'],
                'recipe_name' => (string) $recipe['name'],
                'product_item_id' => $itemId,
                'product_name' => (string) $item['product_name'],
                'item_name' => (string) $item['item_name'],
                'old_price' => (string) $item['price'],
                'new_price' => $price,
                'hpp_per_jar' => $hppPerJar,
                'below_hpp' => $price < $hppPerJar,
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
