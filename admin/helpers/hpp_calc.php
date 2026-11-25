<?php

if (!function_exists('hpp_parse_number')) {
    function hpp_parse_number($value, string $field): float
    {
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            throw new InvalidArgumentException("$field harus berupa angka.");
        }

        if (is_string($value)) {
            $value = trim($value);
            if (substr_count($value, ',') === 1 && strpos($value, '.') === false) {
                $value = str_replace(',', '.', $value);
            }
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException("$field harus berupa angka yang valid.");
        }

        $number = (float) $value;
        if (!is_finite($number)) {
            throw new InvalidArgumentException("$field harus berupa angka yang valid.");
        }

        return $number;
    }
}

if (!function_exists('hpp_parse_positive_integer')) {
    function hpp_parse_positive_integer($value, string $field): int
    {
        $number = hpp_parse_number($value, $field);
        if ($number < 1 || floor($number) !== $number || $number > PHP_INT_MAX) {
            throw new InvalidArgumentException("$field harus berupa bilangan bulat positif.");
        }

        return (int) $number;
    }
}

if (!function_exists('hpp_unit_cost')) {
    function hpp_unit_cost($packPrice, $packSize): float
    {
        $packPrice = hpp_parse_number($packPrice, 'Harga kemasan');
        $packSize = hpp_parse_number($packSize, 'Isi kemasan');

        if ($packPrice < 0) {
            throw new InvalidArgumentException('Harga kemasan tidak boleh negatif.');
        }
        if ($packSize <= 0) {
            throw new InvalidArgumentException('Isi kemasan harus lebih besar dari nol.');
        }

        $unitCost = $packPrice / $packSize;
        if (!is_finite($unitCost)) {
            throw new InvalidArgumentException('Biaya satuan terlalu besar untuk dihitung.');
        }

        return round($unitCost, 6, PHP_ROUND_HALF_UP);
    }
}

/**
 * Hitung HPP resep dari snapshot harga bahan dan biaya operasional.
 *
 * @return array{
 *   material_cost: float,
 *   operational_cost: float,
 *   line_costs: array<int, float>,
 *   cost_lines: array<int, array{calc_type: string, calculated_cost: float}>,
 *   total: float,
 *   rounded_total: float,
 *   hpp_recipe: int,
 *   hpp_per_jar: float
 * }
 */
if (!function_exists('hpp_calculate')) {
    function hpp_calculate(array $lines, array $costs, array $recipe): array
    {
        if (count($lines) > 40) {
            throw new InvalidArgumentException('Maksimal 40 baris bahan per resep.');
        }
        if (count($costs) > 15) {
            throw new InvalidArgumentException('Maksimal 15 baris biaya operasional per resep.');
        }
        if (!array_key_exists('yield_jars', $recipe) || !array_key_exists('round_step', $recipe)) {
            throw new InvalidArgumentException('Jumlah kemasan dan langkah pembulatan wajib diisi.');
        }

        $yieldJars = hpp_parse_positive_integer($recipe['yield_jars'], 'Jumlah kemasan');
        if ($yieldJars > 200) {
            throw new InvalidArgumentException('Jumlah kemasan maksimal 200.');
        }
        $roundStep = hpp_parse_positive_integer($recipe['round_step'], 'Langkah pembulatan');

        $materialCost = 0.0;
        $lineCosts = [];
        foreach ($lines as $index => $line) {
            if (!is_array($line) || !array_key_exists('qty', $line) || !array_key_exists('unit_cost', $line)) {
                throw new InvalidArgumentException('Setiap baris bahan harus memiliki jumlah dan biaya satuan.');
            }

            $quantity = hpp_parse_number($line['qty'], 'Jumlah bahan');
            $unitCost = hpp_parse_number($line['unit_cost'], 'Biaya satuan');
            if ($quantity <= 0) {
                throw new InvalidArgumentException('Jumlah bahan harus lebih besar dari nol.');
            }
            if ($unitCost < 0) {
                throw new InvalidArgumentException('Biaya satuan tidak boleh negatif.');
            }

            $lineCost = $quantity * $unitCost;
            if (!is_finite($lineCost)) {
                throw new InvalidArgumentException('Biaya bahan terlalu besar untuk dihitung.');
            }

            $lineCosts[$index] = $lineCost;
            $materialCost += $lineCost;
        }

        $operationalCost = 0.0;
        $costLines = [];
        foreach ($costs as $cost) {
            if (!is_array($cost) || !array_key_exists('calc_type', $cost) || !array_key_exists('amount', $cost)) {
                throw new InvalidArgumentException('Setiap biaya operasional harus memiliki jenis dan nilai.');
            }

            $calcType = $cost['calc_type'];
            if (!in_array($calcType, ['pct_bahan', 'per_jar', 'flat'], true)) {
                throw new InvalidArgumentException('Jenis biaya operasional tidak valid.');
            }

            $amount = hpp_parse_number($cost['amount'], 'Nilai biaya operasional');
            if ($amount < 0) {
                throw new InvalidArgumentException('Biaya operasional tidak boleh negatif.');
            }
            if ($calcType === 'pct_bahan' && $amount > 100) {
                throw new InvalidArgumentException('Persentase biaya operasional harus antara 0 dan 100.');
            }

            if ($calcType === 'pct_bahan') {
                $lineAmount = $materialCost * $amount / 100;
            } elseif ($calcType === 'per_jar') {
                $lineAmount = $amount * $yieldJars;
            } else {
                $lineAmount = $amount;
            }

            if (!is_finite($lineAmount)) {
                throw new InvalidArgumentException('Biaya operasional terlalu besar untuk dihitung.');
            }

            $costLines[] = [
                'calc_type' => $calcType,
                'calculated_cost' => $lineAmount,
            ];
            $operationalCost += $lineAmount;
        }

        $total = $materialCost + $operationalCost;
        if (!is_finite($total) || $total > PHP_INT_MAX / 100) {
            throw new InvalidArgumentException('Total biaya terlalu besar untuk dihitung.');
        }

        $roundedTotal = round($total, 2, PHP_ROUND_HALF_UP);
        $totalCents = (int) round($roundedTotal * 100, 0, PHP_ROUND_HALF_UP);
        if ($totalCents === 0) {
            $hppRecipe = 0;
        } elseif ($roundStep > intdiv(PHP_INT_MAX, 100)) {
            $hppRecipe = $roundStep;
        } else {
            $stepCents = $roundStep * 100;
            $stepCount = intdiv($totalCents, $stepCents);
            if ($totalCents % $stepCents !== 0) {
                $stepCount++;
            }
            if ($stepCount > intdiv(PHP_INT_MAX, $roundStep)) {
                throw new InvalidArgumentException('HPP resep terlalu besar untuk disimpan.');
            }
            $hppRecipe = $stepCount * $roundStep;
        }

        return [
            'material_cost' => $materialCost,
            'operational_cost' => $operationalCost,
            'line_costs' => $lineCosts,
            'cost_lines' => $costLines,
            'total' => $total,
            'rounded_total' => $roundedTotal,
            'hpp_recipe' => $hppRecipe,
            'hpp_per_jar' => $hppRecipe / $yieldJars,
        ];
    }
}

/**
 * Buat skenario markup dengan harga rekomendasi per jar dibulatkan ke atas.
 *
 * @return array<int, array{
 *   markup_percent: float,
 *   price_per_jar: int,
 *   profit_per_jar: float,
 *   real_margin_percent: float
 * }>
 */
if (!function_exists('hpp_price_scenarios')) {
    function hpp_price_scenarios($hppRecipe, $yieldJars, array $markups): array
    {
        $hppRecipe = hpp_parse_number($hppRecipe, 'HPP resep');
        if ($hppRecipe < 0 || $hppRecipe > PHP_INT_MAX) {
            throw new InvalidArgumentException('HPP resep tidak valid.');
        }
        $yieldJars = hpp_parse_positive_integer($yieldJars, 'Jumlah kemasan');
        if ($yieldJars > 200) {
            throw new InvalidArgumentException('Jumlah kemasan maksimal 200.');
        }

        $hppPerJar = $hppRecipe / $yieldJars;
        $scenarios = [];
        foreach ($markups as $markup) {
            $markup = hpp_parse_number($markup, 'Markup');
            if ($markup < 0) {
                throw new InvalidArgumentException('Markup tidak boleh negatif.');
            }

            $theoreticalPrice = $hppPerJar * (1 + $markup / 100);
            if (!is_finite($theoreticalPrice) || $theoreticalPrice > PHP_INT_MAX) {
                throw new InvalidArgumentException('Harga rekomendasi terlalu besar untuk dihitung.');
            }

            $pricePerJar = (int) ceil(round($theoreticalPrice, 2, PHP_ROUND_HALF_UP));
            $profitPerJar = $pricePerJar - $hppPerJar;
            $scenarios[] = [
                'markup_percent' => $markup,
                'price_per_jar' => $pricePerJar,
                'profit_per_jar' => $profitPerJar,
                'real_margin_percent' => $pricePerJar > 0 ? ($profitPerJar / $pricePerJar) * 100 : 0.0,
            ];
        }

        return $scenarios;
    }
}
