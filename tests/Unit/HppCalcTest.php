<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/helpers/hpp_calc.php';

class HppCalcTest extends TestCase
{
    public function testCalculatesUnitCostFromPackageAndRoundsSnapshotToSixDecimals(): void
    {
        $this->assertSame(32.608696, hpp_unit_cost(15000, 460));
        $this->assertSame(45.0, hpp_unit_cost(45000, 1000));
    }

    /**
     * @dataProvider pdfRecipeTotals
     */
    public function testPdfGoldenRecipeTotals(
        float $materialCost,
        int $expectedDisplayedMaterialCost,
        int $expectedDisplayedOperationalCost,
        int $expectedDisplayedTotal,
        int $expectedHppRecipe,
        int $expectedHppPerJar
    ): void {
        $result = hpp_calculate(
            [['qty' => 1, 'unit_cost' => $materialCost]],
            [
                ['calc_type' => 'pct_bahan', 'amount' => 5],
                ['calc_type' => 'pct_bahan', 'amount' => 5],
                ['calc_type' => 'pct_bahan', 'amount' => 5],
                ['calc_type' => 'per_jar', 'amount' => 4000],
            ],
            ['yield_jars' => 5, 'round_step' => 1000]
        );

        $this->assertTrue(
            abs((int) round($result['material_cost'], 0, PHP_ROUND_HALF_UP) - $expectedDisplayedMaterialCost) <= 1,
            'Biaya bahan harus cocok dengan PDF dengan toleransi maksimal Rp1.'
        );
        $this->assertSame($expectedDisplayedOperationalCost, (int) round($result['operational_cost'], 0, PHP_ROUND_HALF_UP));
        $this->assertSame($expectedDisplayedTotal, (int) round($result['total'], 0, PHP_ROUND_HALF_UP));
        $this->assertSame($expectedHppRecipe, $result['hpp_recipe']);
        $this->assertEquals($expectedHppPerJar, $result['hpp_per_jar']);
    }

    public static function pdfRecipeTotals(): array
    {
        return [
            'Mango Coconut' => [93459.0, 93459, 34019, 127478, 128000, 25600],
            'Avocado Coffee' => [86524.0, 86524, 32979, 119503, 120000, 24000],
            'Strawberry Lychee' => [97471.0, 97471, 34621, 132092, 133000, 26600],
            'Orange Longan' => [94308.8, 94308, 34146, 128455, 129000, 25800],
        ];
    }

    public function testCalculatesEachOperationalCostTypeWithoutRoundingLines(): void
    {
        $result = hpp_calculate(
            [
                ['qty' => '0,5', 'unit_cost' => '10,000001'],
                ['qty' => 2, 'unit_cost' => 3.333333],
            ],
            [
                ['calc_type' => 'pct_bahan', 'amount' => 10],
                ['calc_type' => 'per_jar', 'amount' => 2.5],
                ['calc_type' => 'flat', 'amount' => 1.25],
            ],
            ['yield_jars' => 4, 'round_step' => 1]
        );

        $this->assertEquals(11.6666665, $result['material_cost']);
        $this->assertEquals(12.41666665, $result['operational_cost']);
        $this->assertEquals(24.08333315, $result['total']);
        $this->assertSame(25, $result['hpp_recipe']);
        $this->assertSame(6.25, $result['hpp_per_jar']);
        $this->assertCount(2, $result['line_costs']);
        $this->assertCount(3, $result['cost_lines']);
    }

    public function testRoundsRecipeCostUpAtTheSelectedStep(): void
    {
        $exactStep = hpp_calculate(
            [['qty' => 1, 'unit_cost' => 127000]],
            [],
            ['yield_jars' => 1, 'round_step' => 1000]
        );
        $nextStep = hpp_calculate(
            [['qty' => 1, 'unit_cost' => 127000.01]],
            [],
            ['yield_jars' => 1, 'round_step' => 1000]
        );

        $this->assertSame(127000, $exactStep['hpp_recipe']);
        $this->assertSame(128000, $nextStep['hpp_recipe']);
    }

    public function testCreatesMarkupScenariosAndCalculatesRealMarginFromRoundedPrice(): void
    {
        $scenarios = hpp_price_scenarios(128000, 5, [50, 75, 100, 200]);
        $prices = array_column($scenarios, 'price_per_jar');

        $this->assertSame([38400, 44800, 51200, 76800], $prices);
        $this->assertSame(100.0, $scenarios[2]['markup_percent']);
        $this->assertSame(25600.0, $scenarios[2]['profit_per_jar']);
        $this->assertEquals(50.0, $scenarios[2]['real_margin_percent']);
    }

    public function testRoundsScenarioPriceUpToWholeRupiah(): void
    {
        $scenarios = hpp_price_scenarios(10001, 2, [0]);

        $this->assertSame(5001, $scenarios[0]['price_per_jar']);
    }

    /**
     * @dataProvider invalidInputs
     */
    public function testRejectsInvalidInputs(callable $calculate): void
    {
        try {
            $calculate();
        } catch (InvalidArgumentException $exception) {
            $this->assertNotEmpty($exception->getMessage());
            return;
        }

        $this->fail('Input tidak valid seharusnya menghasilkan InvalidArgumentException.');
    }

    public static function invalidInputs(): array
    {
        return [
            'zero yield' => [static function (): void {
                hpp_calculate([], [], ['yield_jars' => 0, 'round_step' => 1000]);
            }],
            'zero rounding step' => [static function (): void {
                hpp_calculate([], [], ['yield_jars' => 1, 'round_step' => 0]);
            }],
            'zero quantity' => [static function (): void {
                hpp_calculate([['qty' => 0, 'unit_cost' => 1]], [], ['yield_jars' => 1, 'round_step' => 1000]);
            }],
            'zero pack size' => [static function (): void {
                hpp_unit_cost(1000, 0);
            }],
            'invalid cost type' => [static function (): void {
                hpp_calculate([], [['calc_type' => 'unknown', 'amount' => 1]], ['yield_jars' => 1, 'round_step' => 1000]);
            }],
            'percentage over 100' => [static function (): void {
                hpp_calculate([], [['calc_type' => 'pct_bahan', 'amount' => 101]], ['yield_jars' => 1, 'round_step' => 1000]);
            }],
        ];
    }
}
