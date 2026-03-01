<?php
namespace Tests\Unit;

use App\Imports\Sheets\CourseCompletionRateSheet;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class MonthlyReportParsingTest extends TestCase
{
    private CourseCompletionRateSheet $sheet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sheet = new CourseCompletionRateSheet();
    }

    public function test_parse_dd_mm_yyyy_date(): void
    {
        $date = $this->sheet->parseDate('20/11/2025');
        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertEquals('2025-11-20', $date->format('Y-m-d'));
    }

    public function test_parse_date_with_leading_zeros(): void
    {
        $date = $this->sheet->parseDate('03/01/2025');
        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertEquals('2025-01-03', $date->format('Y-m-d'));
    }

    public function test_parse_date_returns_null_for_empty(): void
    {
        $this->assertNull($this->sheet->parseDate(''));
        $this->assertNull($this->sheet->parseDate(null));
    }

    public function test_parse_learning_time_hh_mm_ss(): void
    {
        $minutes = $this->sheet->parseDurationMinutes('01:30:45');
        $this->assertEquals(90, $minutes);
    }

    public function test_parse_learning_time_h_mm_ss(): void
    {
        $minutes = $this->sheet->parseDurationMinutes('1:05:00');
        $this->assertEquals(65, $minutes);
    }

    public function test_parse_learning_time_zero(): void
    {
        $minutes = $this->sheet->parseDurationMinutes('0:00:00');
        $this->assertEquals(0, $minutes);
    }

    public function test_parse_learning_time_as_excel_fraction(): void
    {
        // 1 hour = 1/24 of a day ≈ 0.041667
        $minutes = $this->sheet->parseDurationMinutes('0.041667');
        $this->assertEquals(60, $minutes);
    }

    public function test_parse_duration_returns_zero_for_empty(): void
    {
        $this->assertEquals(0, $this->sheet->parseDurationMinutes(''));
        $this->assertEquals(0, $this->sheet->parseDurationMinutes(null));
    }
}
