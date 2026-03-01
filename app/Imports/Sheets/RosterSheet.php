<?php

namespace App\Imports\Sheets;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RosterSheet implements ToCollection, WithChunkReading, ShouldQueue
{
    public int $rowCount = 0;

    private ?array $headerMap = null;

    public function __construct(private string $importId) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $rowArray = $row->toArray();

            // First non-empty row that contains an email-like header is the header row.
            if ($this->headerMap === null) {
                if ($this->looksLikeHeaderRow($rowArray)) {
                    $this->headerMap = $this->buildHeaderMap($rowArray);
                }
                continue;
            }

            $email = $this->cell($rowArray, 'email');
            if (empty($email)) {
                continue;
            }

            $email = strtolower(trim((string) $email));
            if ($email === '') {
                continue;
            }

            $nameEn = (string) ($this->cell($rowArray, 'name_en') ?? '');
            $nameTh = (string) ($this->cell($rowArray, 'name_th') ?? '');

            // Use the English name as the primary `name`, fall back to email prefix.
            $displayName = trim($nameEn) ?: trim($nameTh) ?: explode('@', $email)[0];

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $displayName,
                    'password' => Hash::make(Str::random(16)),
                ]
            );

            $rawStatus   = trim((string) ($this->cell($rowArray, 'status') ?? ''));
            $busuuStatus = $this->normalizeStatus($rawStatus);

            $user->update([
                'team'             => trim((string) ($this->cell($rowArray, 'team') ?? '')) ?: null,
                'faculty'          => trim((string) ($this->cell($rowArray, 'faculty') ?? '')) ?: null,
                'major'            => trim((string) ($this->cell($rowArray, 'major') ?? '')) ?: null,
                'external_ref'     => trim((string) ($this->cell($rowArray, 'external_ref') ?? '')) ?: null,
                'busuu_user_group' => trim((string) ($this->cell($rowArray, 'user_group') ?? '')) ?: null,
                'busuu_status'     => $busuuStatus ?: null,
                'busuu_name_en'    => trim($nameEn) ?: null,
                'busuu_name_th'    => trim($nameTh) ?: null,
                'last_imported_at' => now(),
            ]);

            $this->rowCount++;
        }
    }

    // ---------------------------------------------------------------------------
    // Header detection helpers
    // ---------------------------------------------------------------------------

    /**
     * Returns true when the row appears to be the roster header (contains an
     * email-related column).
     */
    private function looksLikeHeaderRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell === null || $cell === '') {
                continue;
            }
            $normalised = $this->normaliseHeaderCell((string) $cell);
            if (str_contains($normalised, 'email') || str_contains($normalised, 'mail')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a map of semantic-key => column-index from the header row.
     *
     * Handles Thai column names as well as "**e-mail" (asterisks stripped).
     */
    private function buildHeaderMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $cell) {
            if ($cell === null || $cell === '') {
                continue;
            }
            $key = $this->normaliseHeaderCell((string) $cell);

            // email: "**e-mail", "email", "e-mail", "อีเมล"
            if (str_contains($key, 'email') || str_contains($key, 'e-mail') || str_contains($key, 'mail')) {
                $map['email'] = $index;
            }
            // team: "TEAM", "ทีม"
            elseif ($key === 'team' || str_contains($key, 'team') || str_contains($key, 'ทีม')) {
                $map['team'] = $index;
            }
            // English name: "ชื่อ-นามสกุล (en)", "(en)"
            elseif (str_contains($key, '(en)') || str_contains($key, 'en)') || str_contains($key, 'name_en')) {
                $map['name_en'] = $index;
            }
            // Thai name: "ชื่อ-นามสกุล (th)", "(th)"
            elseif (str_contains($key, '(th)') || str_contains($key, 'th)') || str_contains($key, 'name_th')) {
                $map['name_th'] = $index;
            }
            // external_ref: "รหัสนักศึกษา/ตำแหน่ง"
            elseif (str_contains($key, 'รหัส') || str_contains($key, 'student_id') || str_contains($key, 'position')) {
                $map['external_ref'] = $index;
            }
            // faculty: "คณะ/หน่วยงาน"
            elseif (str_contains($key, 'คณะ') || str_contains($key, 'faculty') || str_contains($key, 'หน่วยงาน') || str_contains($key, 'department')) {
                $map['faculty'] = $index;
            }
            // major: "สาขา"
            elseif (str_contains($key, 'สาขา') || str_contains($key, 'major') || str_contains($key, 'branch')) {
                $map['major'] = $index;
            }
            // user_group: "ผู้ใช้งาน"
            elseif (str_contains($key, 'ผู้ใช้') || str_contains($key, 'user_group') || $key === 'user') {
                $map['user_group'] = $index;
            }
            // status: "Status"
            elseif (str_contains($key, 'status') || str_contains($key, 'สถานะ')) {
                $map['status'] = $index;
            }
        }

        return $map;
    }

    /**
     * Strip leading/trailing whitespace, asterisks, lower-case, collapse
     * inner spaces to underscores so header names are easy to compare.
     */
    private function normaliseHeaderCell(string $value): string
    {
        $value = trim($value);
        $value = ltrim($value, '*'); // strip leading asterisks like "**e-mail"
        $value = rtrim($value, '*');
        $value = strtolower($value);
        $value = str_replace([' ', "\t"], '_', $value);
        return $value;
    }

    /** Read a cell value by semantic key using the current headerMap. */
    private function cell(array $row, string $key): mixed
    {
        if (!isset($this->headerMap[$key])) {
            return null;
        }
        $index = $this->headerMap[$key];
        return $row[$index] ?? null;
    }

    /**
     * Normalise Status column value.
     * Returns "Active", "Pending", or the trimmed original string.
     */
    private function normalizeStatus(string $raw): string
    {
        $raw   = trim($raw);
        $lower = strtolower($raw);
        if ($lower === 'active') {
            return 'Active';
        }
        if ($lower === 'pending') {
            return 'Pending';
        }
        return $raw;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
