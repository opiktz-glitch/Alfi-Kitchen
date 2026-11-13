<?php
use PHPUnit\Framework\TestCase;

class HppRecipeListPageTest extends TestCase
{
    private const ADMIN_DIR = __DIR__ . '/../../admin';

    public function testRecipeListUsesAdminSecurityAndDisplaysRequiredInformation(): void
    {
        $page = file_get_contents(self::ADMIN_DIR . '/hpp_recipes.php');
        $sidebar = file_get_contents(self::ADMIN_DIR . '/sidebar.php');

        $this->assertStringContainsString("require 'auth_check.php'", $page);
        $this->assertStringContainsString("require 'csrf_check.php'", $page);
        $this->assertStringContainsString('Lihat Website', $page);
        $this->assertStringContainsString('name="csrf_token"', $page);
        $this->assertStringContainsString('HPP per jar', $page);
        $this->assertStringContainsString('Harga jual saat ini', $page);
        $this->assertStringContainsString('Harga bahan berubah', $page);
        $this->assertStringContainsString('hpp_get_recipe_list(', $page);
        $this->assertStringContainsString('hpp-recipe-list-wrap', $page);
        $this->assertStringContainsString('Buat Resep HPP', $page);
        $this->assertStringContainsString('Hapus resep "', $page);
        $this->assertStringContainsString('data-submit-lock', $page);
        $this->assertStringContainsString('class="category-icon-button category-action-primary"', $page);
        $this->assertStringContainsString('class="category-icon-button category-action-secondary"', $page);
        $this->assertStringContainsString('class="category-icon-button category-action-delete"', $page);
        $this->assertStringContainsString('hpp-recipe-delete-form', $page);
        $this->assertStringContainsString("'href' => 'hpp_recipes.php'", $sidebar);
        $this->assertStringContainsString('htmlspecialchars(is_scalar($value) ? (string) $value : \'\', ENT_QUOTES, \'UTF-8\')', $page);
    }

    public function testDuplicateAndDeleteActionsDoNotMutatePublicProductPrices(): void
    {
        $page = file_get_contents(self::ADMIN_DIR . '/hpp_recipes.php');

        $this->assertStringContainsString('name="action" value="duplicate_recipe"', $page);
        $this->assertStringContainsString('name="action" value="delete_recipe"', $page);
        $this->assertStringContainsString('hpp_duplicate_recipe(', $page);
        $this->assertStringContainsString('hpp_delete_recipe(', $page);
        $this->assertStringContainsString('HPP_DUPLICATE_RECIPE', $page);
        $this->assertStringContainsString('HPP_DELETE_RECIPE', $page);
        $this->assertStringContainsString('Harga publik produk tidak berubah', $page);
        $this->assertStringNotContainsString('UPDATE product_items', strtoupper($page));
        $this->assertStringContainsString('@media (max-width: 768px)', file_get_contents(self::ADMIN_DIR . '/assets/css/admin.css'));
        $this->assertStringContainsString('.hpp-recipe-list-table tbody tr', file_get_contents(self::ADMIN_DIR . '/assets/css/admin.css'));
        $css = file_get_contents(self::ADMIN_DIR . '/assets/css/admin.css');
        $this->assertStringContainsString('.hpp-recipe-actions .category-icon-button', $css);
        $this->assertMatchesRegularExpression('/\.hpp-recipe-actions\s*\{\s*display:\s*flex;\s*flex-wrap:\s*nowrap;/', $css);
        $this->assertDoesNotMatchRegularExpression('/\.hpp-recipe-delete-form\s*\{[^}]*border-left:/', $css);
    }
}
