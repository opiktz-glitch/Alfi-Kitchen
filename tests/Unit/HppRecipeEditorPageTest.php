<?php
use PHPUnit\Framework\TestCase;

class HppRecipeEditorPageTest extends TestCase
{
    private const ADMIN_DIR = __DIR__ . '/../../admin';

    public function testRecipeEditorUsesAdminSecurityAndServerPersistence(): void
    {
        $page = file_get_contents(self::ADMIN_DIR . '/hpp_edit.php');

        $this->assertStringContainsString("require 'auth_check.php'", $page);
        $this->assertStringContainsString("require 'csrf_check.php'", $page);
        $this->assertStringContainsString('name="csrf_token"', $page);
        $this->assertStringContainsString('name="action" value="save_recipe"', $page);
        $this->assertStringContainsString('hpp_save_recipe(', $page);
        $this->assertStringContainsString('hpp_recipe_has_changed_ingredient_prices(', $page);
        $this->assertStringContainsString('Perbarui snapshot harga bahan master sebelum menyimpan', $page);
        $this->assertStringContainsString('assets/js/hpp_recipe_editor.js', $page);
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $page);
    }

    public function testLiveEditorCalculatesCostsAndDoesNotApplyPublicPrices(): void
    {
        $page = file_get_contents(self::ADMIN_DIR . '/hpp_edit.php');
        $script = file_get_contents(self::ADMIN_DIR . '/assets/js/hpp_recipe_editor.js');
        $sidebar = file_get_contents(self::ADMIN_DIR . '/sidebar.php');

        $this->assertStringContainsString('Pratinjau Kalkulasi', $page);
        $this->assertStringContainsString('hpp-editor-data', $page);
        $this->assertStringContainsString('hpp_recipe_editor.js', $sidebar . $page);
        $this->assertStringContainsString('materialCost * value / 100', $script);
        $this->assertStringContainsString('Math.ceil(totalCents / stepCents)', $script);
        $this->assertStringContainsString('editorData.margins.forEach', $script);
        $this->assertStringContainsString('tidak mengubah harga jual di website', $page);
        $this->assertStringNotContainsString('UPDATE product_items', strtoupper($page));
        $this->assertStringContainsString("'href' => 'hpp_recipes.php'", $sidebar);
        $this->assertStringContainsString('href="hpp_ingredients.php"', $page);
        $this->assertStringContainsString('tab-pane-materials', $page);
        $this->assertStringContainsString('tab-pane-costs', $page);
        $this->assertStringContainsString('tab-pane-calculation', $page);
        $this->assertStringContainsString('data-tab-hpp-badge', $page);
        $this->assertStringContainsString('data-tab-hpp-badge', $script);
        $this->assertStringContainsString('btn-next-to-calculation', $page);
        $this->assertStringContainsString('custom-markup-input', $page);
        $this->assertStringContainsString('custom-scenario-item', $page);
        $this->assertStringContainsString('btn-use-custom-scenario', $page);
        $this->assertStringContainsString('updateCustomScenario', $script);
    }

    public function testPublicPriceApplicationRequiresReviewAndExplicitConfirmation(): void
    {
        $page = file_get_contents(self::ADMIN_DIR . '/hpp_edit.php');
        $repository = file_get_contents(self::ADMIN_DIR . '/helpers/hpp_recipe_repository.php');

        $this->assertStringContainsString('name="action" value="preview_apply_price"', $page);
        $this->assertStringContainsString('name="action" value="apply_public_price"', $page);
        $this->assertStringContainsString('name="expected_old_price"', $page);
        $this->assertStringContainsString('Tinjau Perubahan Harga', $page);
        $this->assertStringContainsString('Pembulatan hanya mengisi harga usulan', $page);
        $this->assertStringContainsString('data-submit-lock', $page);
        $this->assertStringContainsString('Konfirmasi dan Pakai sebagai Harga Jual', $page);
        $this->assertStringContainsString('Harga lama', $page);
        $this->assertStringContainsString('Harga baru', $page);
        $this->assertStringContainsString('Peringatan ini bersifat informatif', $page);
        $this->assertStringContainsString('hpp_apply_public_price(', $page);
        $this->assertStringContainsString('hpp_get_public_price_preview(', $page);
        $this->assertStringContainsString('UPDATE product_items SET price = ?', $repository);
        $this->assertStringContainsString('UPDATE hpp_recipes SET product_item_id = ?', $repository);
    }

    public function testEditorRowsFitBesideCalculatorAtLaptopWidths(): void
    {
        $css = file_get_contents(self::ADMIN_DIR . '/assets/css/admin.css');

        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*1440px\)\s*\{\s*\.hpp-editor-row\s*\{\s*grid-template-columns:\s*repeat\(2,\s*minmax\(0,\s*1fr\)\);/s',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*1440px\)\s*\{\s*\.hpp-cost-row\s*\{\s*grid-template-columns:\s*repeat\(2,\s*minmax\(0,\s*1fr\)\);/s',
            $css
        );
        $this->assertStringContainsString('box-sizing: border-box;', $css);
        $this->assertStringContainsString('min-height: 0;', $css);
        $this->assertStringNotContainsString('max-height: calc(100vh - 32px);', $css);
        $this->assertStringContainsString('@media (max-width: 1200px)', $css);
    }
}
