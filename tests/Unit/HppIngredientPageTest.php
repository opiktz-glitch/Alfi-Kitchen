<?php
use PHPUnit\Framework\TestCase;

class HppIngredientPageTest extends TestCase
{
    private const ADMIN_DIR = __DIR__ . '/../../admin';

    public function testMasterPageUsesAdminSecurityAndEscapedOutput(): void
    {
        $page = file_get_contents(self::ADMIN_DIR . '/hpp_ingredients.php');

        $this->assertStringContainsString("require 'auth_check.php'", $page);
        $this->assertStringContainsString("require 'csrf_check.php'", $page);
        $this->assertStringContainsString('name="csrf_token"', $page);
        $this->assertStringContainsString('htmlspecialchars(is_scalar($value) ? (string) $value : \'\', ENT_QUOTES, \'UTF-8\')', $page);
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $page);
        $this->assertStringContainsString('SHOW TABLES', $page);
        $this->assertStringContainsString('Terapkan migrasi database terlebih dahulu', $page);
    }

    public function testMasterPageProvidesAddEditDeleteAndRecipeUsageHandling(): void
    {
        $page = file_get_contents(self::ADMIN_DIR . '/hpp_ingredients.php');
        $sidebar = file_get_contents(self::ADMIN_DIR . '/sidebar.php');

        $this->assertStringContainsString('name="action" value="save_ingredient"', $page);
        $this->assertStringContainsString('name="action" value="delete_ingredient"', $page);
        $this->assertStringContainsString('COUNT(DISTINCT l.recipe_id)', $page);
        $this->assertStringContainsString('snapshot harga resep tidak berubah', $page);
        $this->assertStringContainsString('hpp_validate_ingredient_input', $page);
        $this->assertStringContainsString('Satuan dasar tidak dapat diubah', $page);
        $this->assertStringContainsString('Bahan tidak dapat dihapus karena sudah dipakai resep', $page);
        $this->assertStringContainsString('HPP_UPDATE_INGREDIENT', $page);
        $this->assertStringContainsString('HPP_ADD_INGREDIENT', $page);
        $this->assertStringContainsString('HPP_DELETE_INGREDIENT', $page);
        $this->assertStringContainsString("'href' => 'hpp_ingredients.php'", $sidebar);
    }
}
