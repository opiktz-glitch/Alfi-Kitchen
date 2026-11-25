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
        $this->assertStringContainsString('HPP per kemasan', $page);
        $this->assertStringContainsString('Harga jual saat ini', $page);
        $this->assertStringContainsString('Harga bahan berubah', $page);
        $this->assertStringContainsString('hpp_get_recipe_list(', $page);
        $this->assertStringContainsString('hpp-recipe-list-wrap', $page);
        $this->assertStringContainsString('Buat Resep HPP', $page);
        $this->assertStringContainsString('Hapus resep "', $page);
        $this->assertStringContainsString('data-submit-lock', $page);
        $this->assertStringContainsString('class="category-icon-button category-action-primary"', $page);
        $this->assertStringContainsString('class="category-icon-button category-action-secondary"', $page);
        $this->assertStringContainsString('class="category-icon-button category-action-view"', $page);
        $this->assertStringContainsString('class="category-icon-button category-action-delete"', $page);
        $this->assertStringContainsString('hpp_view.php?id=', $page);
        $this->assertStringContainsString('hpp-view-modal', $page);
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
        $css = resolve_css_imports(self::ADMIN_DIR . '/assets/css/admin.css');
        $this->assertStringContainsString('@media (max-width: 768px)', $css);
        $this->assertStringContainsString('.hpp-recipe-list-table tbody tr', $css);
        $this->assertStringContainsString('.hpp-recipe-actions .category-icon-button', $css);
        $this->assertMatchesRegularExpression('/\.hpp-recipe-actions\s*\{\s*display:\s*flex;\s*flex-wrap:\s*nowrap;/', $css);
        $this->assertDoesNotMatchRegularExpression('/\.hpp-recipe-delete-form\s*\{[^}]*border-left:/', $css);
    }

    public function testRecipeViewPageRendersCompleteRecipeSheetAndHasStrictSecurity(): void
    {
        $viewPage = file_get_contents(self::ADMIN_DIR . '/hpp_view.php');
        $recipesPage = file_get_contents(self::ADMIN_DIR . '/hpp_recipes.php');
        $css = resolve_css_imports(self::ADMIN_DIR . '/assets/css/admin.css');

        $this->assertStringContainsString("require 'auth_check.php'", $viewPage);
        $this->assertStringNotContainsString('style="', $viewPage, 'hpp_view.php tidak boleh menggunakan inline style');
        $this->assertStringContainsString('hpp-recipe-sheet', $viewPage);
        $this->assertStringContainsString('Bahan Baku', $viewPage);
        $this->assertStringContainsString('Operasional', $viewPage);
        $this->assertStringContainsString('Pembulatan (Harga 1 Resep)', $viewPage);
        $this->assertStringContainsString('Harga per kemasan', $viewPage);
        $this->assertStringNotContainsString('Rencana Margin', $viewPage);
        $this->assertStringContainsString('window.print()', $viewPage);
        $this->assertStringContainsString('hpp-mobile-bottom-bar', $viewPage);
        $this->assertStringContainsString('mobile-only', $viewPage);
        $this->assertStringContainsString('desktop-only', $viewPage);
        $this->assertStringContainsString('Simpan sebagai PDF', $viewPage);
        $this->assertStringContainsString('window.innerWidth <= 768', $recipesPage);
        $this->assertStringContainsString('.hpp-mobile-bottom-bar', $css);
        $this->assertStringContainsString('.qty-unit-inline.mobile-only', $css);
        $this->assertStringContainsString('htmlspecialchars(is_scalar($value) ? (string) $value : \'\', ENT_QUOTES, \'UTF-8\')', $viewPage);
    }
}
