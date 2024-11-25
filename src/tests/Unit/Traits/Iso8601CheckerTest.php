<?php

namespace Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use App\Traits\Iso8601Checker;

class Iso8601CheckerTest extends TestCase
{
    use Iso8601Checker;

    /**
     * Test valid ISO 8601 date formats.
     *
     * @return void
     */
    public function testValidIso8601DatesSuccess(): void
    {
        $isIso8601Date = '2024-11-24T14:45:00+00:00';
        $this->assertTrue($this->isIso8601Date($isIso8601Date));
    }

    /**
     * Test invalid ISO 8601 date formats.
     *
     * @return void
     */
    public function testInvalidIso8601Dates(): void
    {
        $invalidDates = [
            '2024-11-24T14:45:00Zz',  // Invalid timezone format
            '2024/11/24 14:45:00',    // Invalid format
            '2024-11-24T14:45:00+100',// Invalid timezone offset
            '2024-11-24T14:45:00.000Z',// Invalid precision for ISO 8601
        ];

        foreach ($invalidDates as $date) {
            $this->assertFalse($this->isIso8601Date($date), "Failed asserting that '$date' is an invalid ISO 8601 date.");
        }
    }
}
