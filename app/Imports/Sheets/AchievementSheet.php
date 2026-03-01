<?php
namespace App\Imports\Sheets;

use App\Models\Achievement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AchievementSheet implements ToCollection, WithHeadingRow
{
    public int $rowCount = 0;

    private string $achievementName;

    public function __construct(string $achievementName)
    {
        $this->achievementName = $achievementName;
    }

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

    private function parseDate(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        $str = trim((string) $value);

        try {
            return Carbon::createFromFormat('d/m/Y', $str);
        } catch (\Exception $e) {
        }

        try {
            return Carbon::parse($str);
        } catch (\Exception $e) {
        }

        return null;
    }
}
