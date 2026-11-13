<?php
require_once __DIR__ . '/hpp_calc.php';

if (!function_exists('hpp_validate_ingredient_input')) {
    function hpp_validate_ingredient_input(array $input): array
    {
        $name = trim(is_string($input['name'] ?? null) ? $input['name'] : '');
        $baseUnit = is_string($input['base_unit'] ?? null) ? $input['base_unit'] : '';
        $packSizeInput = trim(is_string($input['pack_size'] ?? null) ? $input['pack_size'] : '');
        $packPriceInput = trim(is_string($input['pack_price'] ?? null) ? $input['pack_price'] : '');
        $note = trim(is_string($input['note'] ?? null) ? $input['note'] : '');
        $nameLength = preg_match_all('/./us', $name, $matches);
        $noteLength = preg_match_all('/./us', $note, $matches);
        $nameLength = $nameLength === false ? strlen($name) : $nameLength;
        $noteLength = $noteLength === false ? strlen($note) : $noteLength;

        if ($name === '' || $nameLength > 120) {
            throw new InvalidArgumentException('Nama bahan wajib diisi dan maksimal 120 karakter.');
        }
        if (!in_array($baseUnit, ['g', 'ml', 'pcs'], true)) {
            throw new InvalidArgumentException('Satuan dasar bahan tidak valid.');
        }
        if (!preg_match('/^\d+(?:[.,]\d{1,3})?$/', $packSizeInput)) {
            throw new InvalidArgumentException('Isi kemasan harus angka positif dengan maksimal 3 angka desimal.');
        }
        if (!preg_match('/^\d+$/', $packPriceInput)) {
            throw new InvalidArgumentException('Harga kemasan harus berupa rupiah bulat tanpa pemisah.');
        }
        if ($noteLength > 255) {
            throw new InvalidArgumentException('Catatan maksimal 255 karakter.');
        }

        $packSize = hpp_parse_number($packSizeInput, 'Isi kemasan');
        $normalizedPackPrice = ltrim($packPriceInput, '0');
        $normalizedPackPrice = $normalizedPackPrice === '' ? '0' : $normalizedPackPrice;
        if ($packSize <= 0 || $packSize > 999999999.999) {
            throw new InvalidArgumentException('Isi kemasan harus lebih besar dari nol dan tidak melebihi batas database.');
        }
        if (
            strlen($normalizedPackPrice) > 10
            || (strlen($normalizedPackPrice) === 10 && strcmp($normalizedPackPrice, '2147483647') > 0)
        ) {
            throw new InvalidArgumentException('Harga kemasan harus antara Rp0 dan Rp2.147.483.647.');
        }

        return [
            'name' => $name,
            'base_unit' => $baseUnit,
            'pack_size' => str_replace(',', '.', $packSizeInput),
            'pack_price' => (int) $normalizedPackPrice,
            'note' => $note,
        ];
    }
}
