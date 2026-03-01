<?php

namespace Tests\Unit;

use App\Imports\Sheets\RosterSheet;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class RosterSheetTest extends TestCase
{
    private RosterSheet $sheet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sheet = new RosterSheet('test-import-id');
    }

    // -------------------------------------------------------------------------
    // Helper: drive collection() without a real DB by catching the DB call
    // -------------------------------------------------------------------------

    /**
     * Call the private normalizeStatus method via reflection.
     */
    private function normalizeStatus(string $raw): string
    {
        $ref    = new \ReflectionMethod(RosterSheet::class, 'normalizeStatus');
        return $ref->invoke($this->sheet, $raw);
    }

    /**
     * Call the private normaliseHeaderCell method via reflection.
     */
    private function normaliseHeaderCell(string $value): string
    {
        $ref = new \ReflectionMethod(RosterSheet::class, 'normaliseHeaderCell');
        return $ref->invoke($this->sheet, $value);
    }

    /**
     * Call the private looksLikeHeaderRow method via reflection.
     */
    private function looksLikeHeaderRow(array $row): bool
    {
        $ref = new \ReflectionMethod(RosterSheet::class, 'looksLikeHeaderRow');
        return $ref->invoke($this->sheet, $row);
    }

    /**
     * Call the private buildHeaderMap method via reflection.
     */
    private function buildHeaderMap(array $row): array
    {
        $ref = new \ReflectionMethod(RosterSheet::class, 'buildHeaderMap');
        return $ref->invoke($this->sheet, $row);
    }

    // -------------------------------------------------------------------------
    // normalizeStatus tests
    // -------------------------------------------------------------------------

    public function test_normalize_status_active_mixed_case(): void
    {
        $this->assertSame('Active', $this->normalizeStatus('active'));
        $this->assertSame('Active', $this->normalizeStatus('Active'));
        $this->assertSame('Active', $this->normalizeStatus('ACTIVE'));
    }

    public function test_normalize_status_pending_mixed_case(): void
    {
        $this->assertSame('Pending', $this->normalizeStatus('pending'));
        $this->assertSame('Pending', $this->normalizeStatus('Pending'));
        $this->assertSame('Pending', $this->normalizeStatus('PENDING'));
    }

    public function test_normalize_status_unknown_passthrough(): void
    {
        $this->assertSame('Inactive', $this->normalizeStatus('Inactive'));
        $this->assertSame('', $this->normalizeStatus(''));
    }

    // -------------------------------------------------------------------------
    // normaliseHeaderCell tests
    // -------------------------------------------------------------------------

    public function test_normalise_header_strips_asterisks(): void
    {
        $this->assertSame('e-mail', $this->normaliseHeaderCell('**e-mail'));
        $this->assertSame('e-mail', $this->normaliseHeaderCell('*e-mail*'));
    }

    public function test_normalise_header_lowercases_and_underscores_spaces(): void
    {
        $this->assertSame('student_id', $this->normaliseHeaderCell('Student ID'));
        $this->assertSame('team', $this->normaliseHeaderCell('TEAM'));
    }

    // -------------------------------------------------------------------------
    // looksLikeHeaderRow tests
    // -------------------------------------------------------------------------

    public function test_detects_header_row_with_email_column(): void
    {
        $row = [null, 'TEAM', '**e-mail', 'ชื่อ-นามสกุล (EN)', 'ชื่อ-นามสกุล (TH)', 'Status'];
        $this->assertTrue($this->looksLikeHeaderRow($row));
    }

    public function test_does_not_detect_data_row_as_header(): void
    {
        $row = [1, 'A', 'alice@example.com', 'Alice Smith', 'อลิซ', 'Active'];
        $this->assertFalse($this->looksLikeHeaderRow($row));
    }

    // -------------------------------------------------------------------------
    // buildHeaderMap tests
    // -------------------------------------------------------------------------

    public function test_header_map_email_with_asterisks(): void
    {
        $headers = [null, 'TEAM', '**e-mail', 'ชื่อ-นามสกุล (EN)', 'ชื่อ-นามสกุล (TH)', 'Status'];
        $map     = $this->buildHeaderMap($headers);

        $this->assertSame(2, $map['email']);
    }

    public function test_header_map_status(): void
    {
        $headers = [null, 'TEAM', '**e-mail', 'ชื่อ-นามสกุล (EN)', 'ชื่อ-นามสกุล (TH)', 'Status'];
        $map     = $this->buildHeaderMap($headers);

        $this->assertSame(5, $map['status']);
    }

    public function test_header_map_team(): void
    {
        $headers = [null, 'TEAM', '**e-mail', 'ชื่อ-นามสกุล (EN)', 'ชื่อ-นามสกุล (TH)', 'Status'];
        $map     = $this->buildHeaderMap($headers);

        $this->assertSame(1, $map['team']);
    }

    public function test_header_map_thai_name_columns(): void
    {
        $headers = ['ลำดับที่', 'TEAM', '**e-mail', 'ชื่อ-นามสกุล (EN)', 'ชื่อ-นามสกุล (TH)', 'Status'];
        $map     = $this->buildHeaderMap($headers);

        $this->assertSame(3, $map['name_en']);
        $this->assertSame(4, $map['name_th']);
    }

    // -------------------------------------------------------------------------
    // chunkSize test
    // -------------------------------------------------------------------------

    public function test_chunk_size_is_positive(): void
    {
        $this->assertGreaterThan(0, $this->sheet->chunkSize());
    }
}
