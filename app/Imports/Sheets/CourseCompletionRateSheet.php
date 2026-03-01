<?php

namespace App\Imports\Sheets;

use App\Models\Course;
use App\Models\LearningSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CourseCompletionRateSheet implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    public int $rowCount = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $email = $row['email'] ?? $row['user_email'] ?? null;
            if (empty($email)) {
                continue;
            }

            $email = strtolower(trim((string) $email));

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $row['name'] ?? $row['full_name'] ?? $row['student_name'] ?? explode('@', $email)[0],
                    'password' => Hash::make(Str::random(16)),
                ]
            );

            $courseName = $row['course'] ?? $row['course_name'] ?? null;
            if (empty($courseName)) {
                continue;
            }
            $courseName = trim((string) $courseName);

            $course = Course::firstOrCreate(
                ['name' => $courseName],
                ['language' => $courseName, 'level' => 'A1', 'color' => '#3B82F6']
            );

            $date = $this->parseDate($row['last_active_on'] ?? null);
            if (!$date) {
                continue;
            }

            $durationMinutes = $this->parseDurationMinutes($row['learning_time'] ?? null);
            $completionRate  = (float) ($row['completion_rate'] ?? $row['completion_rate_'] ?? 0);
            $xp              = (int) ($row['xp'] ?? $row['xp_points'] ?? 0);

            LearningSession::updateOrCreate(
                [
                    'user_id'      => $user->id,
                    'course_id'    => $course->id,
                    'session_date' => $date->format('Y-m-d'),
                ],
                [
                    'duration_minutes' => $durationMinutes,
                    'xp_earned'        => $xp,
                    'completed'        => $completionRate >= 100,
                ]
            );

            $this->rowCount++;
        }
    }

    public function parseDate(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        $str = trim((string) $value);

        // DD/MM/YYYY — Busuu monthly report format
        try {
            return Carbon::createFromFormat('d/m/Y', $str);
        } catch (\Exception $e) {
            // ignore
        }

        // Fallback to generic parse
        try {
            return Carbon::parse($str);
        } catch (\Exception $e) {
            // ignore
        }

        return null;
    }

    public function parseDurationMinutes(mixed $value): int
    {
        if (empty($value)) {
            return 0;
        }

        $str = trim((string) $value);

        // Excel stores time as a fraction of a day (e.g. 0.0625 = 1h 30m)
        if (is_numeric($str)) {
            return (int) round((float) $str * 1440);
        }

        // hh:mm:ss or h:mm:ss
        $parts = explode(':', $str);
        if (count($parts) >= 2) {
            return (int) $parts[0] * 60 + (int) $parts[1];
        }

        return 0;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}