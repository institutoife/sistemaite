<?php

namespace Tests\Unit;

use App\Services\TelefonoService;
use PHPUnit\Framework\TestCase;

class TelefonoServiceTest extends TestCase
{
    /** @dataProvider phoneCases */
    public function test_normalizes_without_modifying_original($original, $expected)
    {
        $this->assertSame($expected, (new TelefonoService)->normalize($original));
    }

    public function phoneCases(): array
    {
        return [
            ['60902299', '+59160902299'], ['+591 6090-2299', '+59160902299'],
            ['59160902299', '+59160902299'], ['0059160902299', '+59160902299'],
            ['+34 612 345 678', '+34612345678'], ['', null], [null, null], ['0', null],
            ['00000000', null], ['   ', null],
            ['12345', null], ['60902299 / 71002299', null], ['+5911234', null], ['abcdef', null],
        ];
    }

    public function test_missing_imported_numbers_are_distinct_from_invalid_present_numbers()
    {
        $service = new TelefonoService;
        foreach ([null, '', '  ', '0', '00000000'] as $missing) {
            $this->assertTrue($service->isMissing($missing));
        }
        foreach (['12345', 'abc', '60902299'] as $present) {
            $this->assertFalse($service->isMissing($present));
        }
    }
}
