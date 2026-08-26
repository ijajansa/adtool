<?php

namespace Tests\Unit;

use App\Services\Meta\Publishing\MetaBudgetConverter;
use InvalidArgumentException;
use Tests\TestCase;

class MetaBudgetConverterTest extends TestCase
{
    public function test_it_converts_decimal_strings_without_floating_point_math(): void
    {
        $converter = app(MetaBudgetConverter::class);
        $this->assertSame(1001, $converter->toMinorUnits('10.01', 'USD'));
        $this->assertSame(50000, $converter->toMinorUnits('500.00', 'INR'));
        $this->assertSame(501, $converter->toMinorUnits('501', 'JPY'));
    }

    public function test_it_rejects_unsupported_precision_and_currency(): void
    {
        $converter = app(MetaBudgetConverter::class);
        try {
            $converter->toMinorUnits('1.001', 'USD');
            $this->fail('Expected excessive precision to be rejected.');
        } catch (InvalidArgumentException) {
            $this->expectException(InvalidArgumentException::class);
            $converter->toMinorUnits('10', 'ZZZ');
        }
    }
}
