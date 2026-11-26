<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/helpers/hpp_recipe_repository.php';

class HppRecipeRepositoryTest extends TestCase
{
    private function createDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('CREATE TABLE products (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        $pdo->exec(
            'CREATE TABLE hpp_ingredients ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, base_unit TEXT NOT NULL, '
            . 'pack_size NUMERIC NOT NULL, pack_price INTEGER NOT NULL, note TEXT NULL)'
        );
        $pdo->exec(
            'CREATE TABLE product_items ('
            . 'id INTEGER PRIMARY KEY, product_id INTEGER NOT NULL, name TEXT NOT NULL, price NUMERIC NOT NULL, '
            . 'FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)'
        );
        $pdo->exec(
            'CREATE TABLE hpp_recipes ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, variant TEXT NOT NULL DEFAULT \'\', '
            . 'yield_jars INTEGER NOT NULL, jar_ml INTEGER NULL, round_step INTEGER NOT NULL, note TEXT NULL, '
            . 'product_item_id INTEGER NULL UNIQUE, total_cost INTEGER NOT NULL DEFAULT 0, '
            . 'hpp_recipe INTEGER NOT NULL DEFAULT 0, hpp_per_jar INTEGER NOT NULL DEFAULT 0, '
            . 'selling_price INTEGER NULL, updated_at TEXT NULL, FOREIGN KEY (product_item_id) REFERENCES product_items(id) ON DELETE SET NULL)'
        );
        $pdo->exec(
            'CREATE TABLE hpp_recipe_lines ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, recipe_id INTEGER NOT NULL, ingredient_id INTEGER NULL, '
            . 'custom_name TEXT NULL, qty NUMERIC NOT NULL, unit_cost NUMERIC NOT NULL, sort_order INTEGER NOT NULL, '
            . 'FOREIGN KEY (recipe_id) REFERENCES hpp_recipes(id) ON DELETE CASCADE, '
            . 'FOREIGN KEY (ingredient_id) REFERENCES hpp_ingredients(id) ON DELETE RESTRICT)'
        );
        $pdo->exec(
            'CREATE TABLE hpp_recipe_costs ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, recipe_id INTEGER NOT NULL, label TEXT NOT NULL, '
            . 'calc_type TEXT NOT NULL, amount NUMERIC NOT NULL, sort_order INTEGER NOT NULL, '
            . 'FOREIGN KEY (recipe_id) REFERENCES hpp_recipes(id) ON DELETE CASCADE)'
        );
        $pdo->exec(
            "INSERT INTO hpp_ingredients (name, base_unit, pack_size, pack_price) "
            . "VALUES ('Susu', 'ml', 100, 5000), ('Gula', 'g', 1000, 20000)"
        );
        $pdo->exec("INSERT INTO products (id, name) VALUES (1, 'Dessert')");
        $pdo->exec("INSERT INTO product_items (id, product_id, name, price) VALUES (1, 1, 'Jar', 15000)");

        return $pdo;
    }

    private function recipePayload(): array
    {
        return [
            'name' => 'Dessert Uji',
            'variant' => 'Creamy',
            'yield_jars' => '5',
            'jar_ml' => '200',
            'round_step' => '1000',
            'note' => 'Resep percobaan',
            'lines' => [
                [
                    'ingredient_id' => '1',
                    'qty' => '10',
                    'unit_cost' => '999999',
                ],
                [
                    'ingredient_id' => '',
                    'custom_name' => 'Topping bebas',
                    'qty' => '0,5',
                    'unit_cost' => '1000',
                ],
            ],
            'costs' => [
                ['label' => 'Tenaga kerja', 'calc_type' => 'pct_bahan', 'amount' => '10'],
                ['label' => 'Packing', 'calc_type' => 'per_jar', 'amount' => '25'],
                ['label' => 'Gas', 'calc_type' => 'flat', 'amount' => '50'],
            ],
        ];
    }

    public function testSavesRecipeSnapshotCostsAndCalculatedSummaryAtomically(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());

        $this->assertSame(1, $saved['id']);
        $this->assertEquals(1000, $saved['calculation']['material_cost']);
        $this->assertSame(275.0, $saved['calculation']['operational_cost']);
        $this->assertSame(1275.0, $saved['calculation']['total']);
        $this->assertSame(2000, $saved['calculation']['hpp_recipe']);
        $this->assertFalse($pdo->inTransaction());

        $recipe = hpp_get_recipe($pdo, $saved['id']);
        $this->assertSame('Dessert Uji', $recipe['name']);
        $this->assertSame('Creamy', $recipe['variant']);
        $this->assertSame(2, count($recipe['lines']));
        $this->assertSame(3, count($recipe['costs']));
        $this->assertEquals(50.0, (float) $recipe['lines'][0]['unit_cost']);
        $this->assertSame('Susu', $recipe['lines'][0]['ingredient_name']);
        $this->assertSame(1275, (int) $recipe['total_cost']);
        $this->assertSame(2000, (int) $recipe['hpp_recipe']);
        $this->assertSame(400, (int) $recipe['hpp_per_jar']);
        $this->assertNull($recipe['product_item_id']);
    }

    public function testEditingRecipePreservesSnapshotsUntilExplicitRefresh(): void
    {
        $pdo = $this->createDatabase();
        $created = hpp_save_recipe($pdo, $this->recipePayload());
        $recipe = hpp_get_recipe($pdo, $created['id']);
        $masterLine = $recipe['lines'][0];

        $pdo->exec('UPDATE hpp_ingredients SET pack_price = 10000 WHERE id = 1');
        $pdo->exec('UPDATE hpp_recipes SET product_item_id = 1 WHERE id = ' . (int) $created['id']);
        $this->assertTrue(hpp_recipe_has_changed_ingredient_prices($pdo, $created['id']));

        $payload = $this->recipePayload();
        $payload['lines'][0]['id'] = (string) $masterLine['id'];
        $payload['lines'][1]['id'] = (string) $recipe['lines'][1]['id'];
        $updated = hpp_save_recipe($pdo, $payload, $created['id']);
        $updatedRecipe = hpp_get_recipe($pdo, $created['id']);

        $this->assertEquals(50.0, (float) $updatedRecipe['lines'][0]['unit_cost']);
        $this->assertSame((int) $masterLine['id'], (int) $updatedRecipe['lines'][0]['id']);
        $this->assertTrue(hpp_recipe_has_changed_ingredient_prices($pdo, $created['id']));
        $this->assertSame(1, (int) $updatedRecipe['product_item_id']);
        $this->assertSame(15000, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());

        hpp_save_recipe($pdo, $payload, $created['id'], true);
        $refreshedRecipe = hpp_get_recipe($pdo, $created['id']);
        $this->assertEquals(100.0, (float) $refreshedRecipe['lines'][0]['unit_cost']);
        $this->assertFalse(hpp_recipe_has_changed_ingredient_prices($pdo, $created['id']));
        $this->assertSame(2000, $updated['calculation']['hpp_recipe']);
    }

    public function testRecipeListIncludesPublicPriceAndChangedSnapshotStatus(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());
        $pdo->prepare('UPDATE hpp_recipes SET product_item_id = ? WHERE id = ?')->execute([1, $saved['id']]);
        $pdo->exec('UPDATE hpp_ingredients SET pack_price = 10000 WHERE id = 1');

        $recipes = hpp_get_recipe_list($pdo);

        $this->assertCount(1, $recipes);
        $this->assertSame($saved['id'], (int) $recipes[0]['id']);
        $this->assertSame('Dessert', $recipes[0]['product_name']);
        $this->assertSame('Jar', $recipes[0]['item_name']);
        $this->assertSame(15000, (int) $recipes[0]['public_price']);
        $this->assertTrue($recipes[0]['ingredient_prices_changed']);
        $this->assertSame(15000, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());
    }

    public function testPriceApplicationPreviewDoesNotMutatePricesAndWarnsBelowHpp(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());

        $preview = hpp_get_public_price_preview($pdo, $saved['id'], '1', '300');

        $this->assertSame('Dessert', $preview['product_name']);
        $this->assertSame('Jar', $preview['item_name']);
        $this->assertSame('15000', (string) $preview['old_price']);
        $this->assertSame(300, $preview['new_price']);
        $this->assertEquals(400.0, $preview['hpp_per_jar']);
        $this->assertTrue($preview['below_hpp']);
        $this->assertNull(hpp_get_recipe($pdo, $saved['id'])['product_item_id']);
        $this->assertSame(15000, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());
    }

    public function testConfirmedPriceApplicationUpdatesPriceAndRecipeLink(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());

        $applied = hpp_apply_public_price($pdo, $saved['id'], '1', '18500', '15000.00');

        $this->assertSame(18500, $applied['new_price']);
        $this->assertSame(15000, hpp_public_price_cents($applied['old_price'], 'Harga lama') / 100);
        $this->assertFalse($applied['below_hpp']);
        $this->assertSame(1, (int) hpp_get_recipe($pdo, $saved['id'])['product_item_id']);
        $this->assertSame(18500, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());
        $this->assertFalse($pdo->inTransaction());
    }

    public function testPriceApplicationRejectsStalePriceWithoutChangingEitherRecord(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());
        $pdo->exec('UPDATE product_items SET price = 16000 WHERE id = 1');

        try {
            hpp_apply_public_price($pdo, $saved['id'], '1', '18500', '15000.00');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('berubah sejak pratinjau', $exception->getMessage());
            $this->assertNull(hpp_get_recipe($pdo, $saved['id'])['product_item_id']);
            $this->assertSame(16000, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());
            $this->assertFalse($pdo->inTransaction());
            return;
        }

        $this->fail('Harga lama yang sudah berubah seharusnya meminta konfirmasi ulang.');
    }

    public function testPriceApplicationRejectsVariantLinkedToAnotherRecipe(): void
    {
        $pdo = $this->createDatabase();
        $first = hpp_save_recipe($pdo, $this->recipePayload());
        $second = hpp_save_recipe($pdo, array_merge($this->recipePayload(), ['name' => 'Resep Kedua']));
        $pdo->prepare('UPDATE hpp_recipes SET product_item_id = ? WHERE id = ?')->execute([1, $first['id']]);

        try {
            hpp_apply_public_price($pdo, $second['id'], '1', '18500', '15000.00');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('terhubung ke resep lain', $exception->getMessage());
            $this->assertSame(1, (int) hpp_get_recipe($pdo, $first['id'])['product_item_id']);
            $this->assertNull(hpp_get_recipe($pdo, $second['id'])['product_item_id']);
            $this->assertSame(15000, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());
            $this->assertFalse($pdo->inTransaction());
            return;
        }

        $this->fail('Varian yang telah terhubung ke resep lain seharusnya ditolak.');
    }

    public function testPriceApplicationRollsBackPriceIfRecipeLinkUpdateFails(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());
        $pdo->exec(
            "CREATE TRIGGER fail_recipe_link BEFORE UPDATE OF product_item_id ON hpp_recipes "
            . "BEGIN SELECT RAISE(ABORT, 'forced link failure'); END"
        );

        try {
            hpp_apply_public_price($pdo, $saved['id'], '1', '18500', '15000.00');
        } catch (PDOException $exception) {
            $this->assertStringContainsString('forced link failure', $exception->getMessage());
            $this->assertNull(hpp_get_recipe($pdo, $saved['id'])['product_item_id']);
            $this->assertSame(15000, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());
            $this->assertFalse($pdo->inTransaction());
            return;
        }

        $this->fail('Gagal menghubungkan resep harus membatalkan perubahan harga.');
    }

    public function testDuplicateRecipeCopiesSnapshotsAndCostsWithoutPublicProductLink(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());
        $original = hpp_get_recipe($pdo, $saved['id']);
        $pdo->prepare('UPDATE hpp_recipes SET product_item_id = ? WHERE id = ?')->execute([1, $saved['id']]);
        $pdo->exec('UPDATE hpp_ingredients SET pack_price = 10000 WHERE id = 1');

        $duplicateId = hpp_duplicate_recipe($pdo, $saved['id']);
        $duplicate = hpp_get_recipe($pdo, $duplicateId);

        $this->assertNotSame($saved['id'], $duplicateId);
        $this->assertSame('Dessert Uji', $duplicate['name']);
        $this->assertSame('Creamy', $duplicate['variant']);
        $this->assertNull($duplicate['product_item_id']);
        $this->assertCount(2, $duplicate['lines']);
        $this->assertCount(3, $duplicate['costs']);
        $this->assertSame((int) $original['lines'][0]['id'] + 2, (int) $duplicate['lines'][0]['id']);
        $this->assertEquals(50.0, (float) $duplicate['lines'][0]['unit_cost']);
        $this->assertTrue(hpp_recipe_has_changed_ingredient_prices($pdo, $duplicateId));
        $this->assertSame(15000, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());
    }

    public function testDeleteRecipeCascadesChildrenAndLeavesPublicPriceUnchanged(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());
        $pdo->prepare('UPDATE hpp_recipes SET product_item_id = ? WHERE id = ?')->execute([1, $saved['id']]);

        $deleted = hpp_delete_recipe($pdo, $saved['id']);

        $this->assertSame('Dessert Uji', $deleted['name']);
        $this->assertSame('Creamy', $deleted['variant']);
        $this->assertSame(1, $deleted['product_item_id']);
        $this->assertNull(hpp_get_recipe($pdo, $saved['id']));
        $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipe_lines')->fetchColumn());
        $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipe_costs')->fetchColumn());
        $this->assertSame(15000, (int) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());
    }

    /**
     * @dataProvider invalidRecipePayloads
     */
    public function testInvalidRecipePayloadDoesNotWritePartialData(array $payload): void
    {
        $pdo = $this->createDatabase();
        try {
            hpp_save_recipe($pdo, $payload);
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty($exception->getMessage());
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipes')->fetchColumn());
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipe_lines')->fetchColumn());
            $this->assertFalse($pdo->inTransaction());
            return;
        }

        $this->fail('Payload resep tidak valid seharusnya ditolak.');
    }

    public static function invalidRecipePayloads(): array
    {
        $valid = [
            'name' => 'Resep',
            'variant' => '',
            'yield_jars' => 1,
            'round_step' => 1000,
            'lines' => [['ingredient_id' => 1, 'qty' => 1]],
            'costs' => [],
        ];

        return [
            'no lines' => [array_merge($valid, ['lines' => []])],
            'too many lines' => [array_merge($valid, ['lines' => array_fill(0, 41, ['ingredient_id' => 1, 'qty' => 1])])],
            'yield above maximum' => [array_merge($valid, ['yield_jars' => 201])],
            'unknown master ingredient' => [array_merge($valid, ['lines' => [['ingredient_id' => 999, 'qty' => 1]]])],
            'zero quantity' => [array_merge($valid, ['lines' => [['ingredient_id' => 1, 'qty' => 0]]])],
            'fractional quantity exceeds database precision' => [array_merge($valid, ['lines' => [['ingredient_id' => 1, 'qty' => '0,1234']]])],
            'percentage above maximum' => [array_merge($valid, ['costs' => [['label' => 'Biaya', 'calc_type' => 'pct_bahan', 'amount' => 100.01]]])],
        ];
    }

    public function testRejectsLineIdBelongingToAnotherRecipe(): void
    {
        $pdo = $this->createDatabase();
        $first = hpp_save_recipe($pdo, $this->recipePayload());
        $second = hpp_save_recipe($pdo, array_merge($this->recipePayload(), ['name' => 'Resep Kedua']));
        $firstRecipe = hpp_get_recipe($pdo, $first['id']);
        $secondRecipe = hpp_get_recipe($pdo, $second['id']);
        $payload = $this->recipePayload();
        $payload['lines'][0]['id'] = (string) $secondRecipe['lines'][0]['id'];
        $payload['lines'][1]['id'] = (string) $firstRecipe['lines'][1]['id'];

        try {
            hpp_save_recipe($pdo, $payload, $first['id']);
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Baris bahan tidak ditemukan', $exception->getMessage());
            $this->assertSame(2, (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipes')->fetchColumn());
            $this->assertFalse($pdo->inTransaction());
            return;
        }

        $this->fail('ID baris bahan dari resep lain seharusnya ditolak.');
    }

    public function testRollsBackRecipeWhenChildInsertFails(): void
    {
        $pdo = $this->createDatabase();
        $pdo->exec(
            "CREATE TRIGGER fail_cost_insert BEFORE INSERT ON hpp_recipe_costs "
            . "BEGIN SELECT RAISE(ABORT, 'forced test failure'); END"
        );

        try {
            hpp_save_recipe($pdo, $this->recipePayload());
        } catch (PDOException $exception) {
            $this->assertStringContainsString('forced test failure', $exception->getMessage());
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipes')->fetchColumn());
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipe_lines')->fetchColumn());
            $this->assertFalse($pdo->inTransaction());
            return;
        }

        $this->fail('Kesalahan penyimpanan baris biaya harus membatalkan seluruh transaksi.');
    }

    public function testSavesRecipeSellingPriceWithoutLinkingProduct(): void
    {
        $pdo = $this->createDatabase();
        $saved = hpp_save_recipe($pdo, $this->recipePayload());
        $recipeId = (int) $saved['id'];

        $result = hpp_save_recipe_selling_price($pdo, $recipeId, 33500);

        $this->assertSame(33500, $result['selling_price']);
        $this->assertFalse($result['below_hpp']);

        $loaded = hpp_get_recipe($pdo, $recipeId);
        $this->assertNull($loaded['product_item_id']);
        $this->assertSame(33500, (int) $loaded['selling_price']);
        $this->assertSame('15000', (string) $pdo->query('SELECT price FROM product_items WHERE id = 1')->fetchColumn());

        $list = hpp_get_recipe_list($pdo);
        $this->assertSame(33500, (int) $list[0]['selling_price']);
        $this->assertNull($list[0]['public_price']);

        $dupId = hpp_duplicate_recipe($pdo, $recipeId);
        $dup = hpp_get_recipe($pdo, $dupId);
        $this->assertNull($dup['product_item_id']);
        $this->assertSame(33500, (int) $dup['selling_price']);
    }
}
