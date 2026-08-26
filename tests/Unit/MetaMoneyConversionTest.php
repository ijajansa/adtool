<?php

namespace Tests\Unit;

use App\Services\Meta\MetaAssetSyncService;
use Tests\TestCase;

class MetaMoneyConversionTest extends TestCase
{
    public function test_meta_minor_units_are_converted_to_normal_currency_units(): void
    {
        $service = app(MetaAssetSyncService::class);

        $this->assertSame('123.45', $service->minorUnitsToMajor('12345'));
        $this->assertSame('0.05', $service->minorUnitsToMajor('5'));
        $this->assertSame('-1.25', $service->minorUnitsToMajor('-125'));
        $this->assertNull($service->minorUnitsToMajor(null));
    }
}
