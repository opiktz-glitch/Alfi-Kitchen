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
        $this->assertStringContainsString('$hasExplicitId = $rawId !== null && $rawId !== \'\' && $rawId !== \'0\'', $page);
    }

    public function testIngredientIdResolutionAllowsZeroForNewItemsAndRejectsInvalidId(): void
    {
        $resolveId = function (?string $rawId): array {
            $hasExplicitId = $rawId !== null && $rawId !== '' && $rawId !== '0';
            $postedId = !$hasExplicitId
                ? 0
                : (is_scalar($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false);
            if ($hasExplicitId && ($postedId === false || $postedId < 1)) {
                throw new InvalidArgumentException('ID bahan tidak valid.');
            }
            return [
                'editingId' => (int) $postedId,
                'isNew' => $postedId === 0,
            ];
        };

        $resultZero = $resolveId('0');
        $this->assertSame(0, $resultZero['editingId']);
        $this->assertTrue($resultZero['isNew']);

        $resultNull = $resolveId(null);
        $this->assertSame(0, $resultNull['editingId']);
        $this->assertTrue($resultNull['isNew']);

        $resultEmpty = $resolveId('');
        $this->assertSame(0, $resultEmpty['editingId']);
        $this->assertTrue($resultEmpty['isNew']);

        $resultValid = $resolveId('5');
        $this->assertSame(5, $resultValid['editingId']);
        $this->assertFalse($resultValid['isNew']);

        try {
            $resolveId('abc');
            $this->fail('ID bahan non-numerik harus ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('ID bahan tidak valid.', $e->getMessage());
        }

        try {
            $resolveId('-1');
            $this->fail('ID bahan negatif harus ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('ID bahan tidak valid.', $e->getMessage());
        }
    }
}
