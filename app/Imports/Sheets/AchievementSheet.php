<?php

namespace App\Imports\Sheets;

use App\Models\Achievement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Services\ImportHistoryService;

class AchievementSheet implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue, WithEvents
{
    public int $rowCount = 0;

    public function __construct(
        private string $achievementName,
        private string $importId
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $email = $row['email'] ?? $row['user_email'] ?? null;
            if (empty($email)) continue;

            $email = strtolower(trim((string) $email));

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $row['name'] ?? $row['full_name'] ?? $row['student_name'] ?? explode('@', $email)[0],
                    'password' => Hash::make(Str::random(16)),
                ]
            );

            $earnedAt   = $this->parseDate($row['date'] ?? $row['earned_at'] ?? $row['achievement_date'] ?? null) ?? now();
            $courseName = $row['course'] ?? $row['course_name'] ?? null;

            Achievement::firstOrCreate(
                ['user_id' => $user->id, 'name' => $this->achievementName, 'type' => 'completion'],
                [
                    'description' => $courseName ? 'Completed: ' . trim((string) $courseName) : null,
                    'earned_at'   => $earnedAt,
                ]
            );

            $this->rowCount++;
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function () {
                // อัปเดต rows แบบ incremental (ไม่ต้องรอจบทั้ง workbook)
                app(ImportHistoryService::class)->update($this->importId, [
                    'rows' => (int) (app(ImportHistoryService::class)->all()[0]['rows'] ?? 0), // optional
                ]);
            },
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (empty($value)) return null;
        $str = trim((string) $value);

        try { return Carbon::createFromFormat('d/m/Y', $str); } catch (\Exception $e) {}
        try { return Carbon::parse($str); } catch (\Exception $e) {}

        return null;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}