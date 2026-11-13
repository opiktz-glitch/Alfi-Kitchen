<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/helpers/hpp_ingredient_validation.php';

class HppIngredientTest extends TestCase
{
    public function testValidatesIngredientAndNormalizesDecimalComma(): void
    {
        $ingredient = hpp_validate_ingredient_input([
            'name' => 'Susu UHT',
            'base_unit' => 'ml',
            'pack_size' => '1000,500',
            'pack_price' => '0019000',
            'note' => 'Kemasan besar',
        ]);

        $this->assertSame('Susu UHT', $ingredient['name']);
        $this->assertSame('ml', $ingredient['base_unit']);
        $this->assertSame('1000.500', $ingredient['pack_size']);
        $this->assertSame(19000, $ingredient['pack_price']);
        $this->assertSame('Kemasan besar', $ingredient['note']);
    }

    /**
     * @dataProvider invalidIngredients
     */
    public function testRejectsInvalidIngredientValues(array $input): void
    {
        try {
            hpp_validate_ingredient_input($input);
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty($exception->getMessage());
            return;
        }

        $this->fail('Data bahan tidak valid seharusnya ditolak.');
    }

    public static function invalidIngredients(): array
    {
        $valid = [
            'name' => 'Bahan uji',
            'base_unit' => 'g',
            'pack_size' => '100',
            'pack_price' => '5000',
            'note' => '',
        ];

        return [
            'empty name' => [array_merge($valid, ['name' => '  '])],
            'non-text name' => [array_merge($valid, ['name' => ['invalid']])],
            'name too long' => [array_merge($valid, ['name' => str_repeat('a', 121)])],
            'unsupported unit' => [array_merge($valid, ['base_unit' => 'kg'])],
            'zero pack size' => [array_merge($valid, ['pack_size' => '0'])],
            'too many pack-size decimals' => [array_merge($valid, ['pack_size' => '0,1234'])],
            'non-integer pack price' => [array_merge($valid, ['pack_price' => '5000.50'])],
            'pack price out of database range' => [array_merge($valid, ['pack_price' => '2147483648'])],
            'note too long' => [array_merge($valid, ['note' => str_repeat('a', 256)])],
        ];
    }
}
