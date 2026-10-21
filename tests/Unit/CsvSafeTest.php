<?php

namespace Tests\Unit;

use App\Support\CsvSafe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CsvSafeTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function formulaTriggers(): array
    {
        return [
            'equals' => ['=HYPERLINK("http://example.test","click")'],
            'plus' => ['+1+1'],
            'minus' => ['-2+3'],
            'at sign' => ['@SUM(A1:A2)'],
            'tab' => ["\t=1+1"],
            'carriage return' => ["\r=1+1"],
        ];
    }

    #[DataProvider('formulaTriggers')]
    public function test_formula_prefixes_are_neutralised(string $value): void
    {
        $this->assertSame("'".$value, CsvSafe::cell($value));
    }

    public function test_ordinary_text_is_left_alone(): void
    {
        $this->assertSame('Green Unleaded', CsvSafe::cell('Green Unleaded'));
        $this->assertSame('Dagatan - Branch', CsvSafe::cell('Dagatan - Branch'));
    }

    public function test_non_strings_and_empty_values_pass_through(): void
    {
        $this->assertNull(CsvSafe::cell(null));
        $this->assertSame('', CsvSafe::cell(''));
        $this->assertSame(-5, CsvSafe::cell(-5));
        $this->assertSame(12.5, CsvSafe::cell(12.5));
    }
}
