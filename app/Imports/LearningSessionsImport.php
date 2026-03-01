<?php
namespace App\Imports;

use App\Models\Course;
use App\Models\LearningSession;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

class LearningSessionsImport implements ToModel, WithHeadingRow, SkipsOnError, WithBatchInserts, WithChunkReading {
    use SkipsErrors;

    public int $rowCount = 0;

    public function model(array $row): ?LearningSession {
        $user = User::where('email', $row['user_email'] ?? '')->first();
        $course = Course::where('name', $row['course_name'] ?? '')->first();

        if (!$user || !$course) return null;

        $this->rowCount++;

        return new LearningSession([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'duration_minutes' => (int) ($row['duration_min'] ?? 0),
            'session_date' => Carbon::parse($row['session_date'])->format('Y-m-d'),
            'xp_earned' => (int) ($row['xp_earned'] ?? 0),
            'completed' => filter_var($row['completed'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function batchSize(): int { return 100; }
    public function chunkSize(): int { return 500; }
}
