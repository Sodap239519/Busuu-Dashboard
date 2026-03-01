<?php
namespace App\Imports\Sheets;

use App\Models\Course;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentProgressSheet implements ToCollection, WithHeadingRow
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

            $lessonsCompleted = (int) ($row['lessons_completed'] ?? $row['completed_lessons'] ?? 0);
            $totalLessons     = (int) ($row['total_lessons'] ?? $course->total_lessons ?? 0);
            $progressPct      = (float) ($row['progress'] ?? $row['progress_percentage'] ?? $row['progress_'] ?? 0);

            if ($progressPct == 0 && $totalLessons > 0) {
                $progressPct = round($lessonsCompleted / $totalLessons * 100, 2);
            }

            $progress = UserProgress::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['started_at' => now()]
            );
            $progress->lessons_completed   = $lessonsCompleted;
            $progress->total_lessons       = $totalLessons ?: $course->total_lessons;
            $progress->progress_percentage = $progressPct;
            $progress->save();

            $this->rowCount++;
        }
    }
}
