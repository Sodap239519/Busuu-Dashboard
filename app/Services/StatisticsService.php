<?php
namespace App\Services;

use App\Models\LearningSession;
use App\Models\UserProgress;
use App\Models\Achievement;
use Illuminate\Support\Facades\DB;

class StatisticsService {
    public function getUserStats(int $userId): array {
        $totalMinutes = LearningSession::where('user_id', $userId)->sum('duration_minutes');
        $totalXp = LearningSession::where('user_id', $userId)->sum('xp_earned');
        $coursesCount = UserProgress::where('user_id', $userId)->count();
        $streak = $this->calculateStreak($userId);
        $completedCourses = UserProgress::where('user_id', $userId)->where('progress_percentage', 100)->count();

        return [
            'total_hours' => round($totalMinutes / 60, 1),
            'total_xp' => $totalXp,
            'courses_count' => $coursesCount,
            'streak_days' => $streak,
            'completed_courses' => $completedCourses,
        ];
    }

    public function getWeeklyActivity(int $userId): array {
        $sessions = LearningSession::where('user_id', $userId)
            ->where('session_date', '>=', now()->subDays(30))
            ->selectRaw('session_date, SUM(duration_minutes) as total_minutes, SUM(xp_earned) as total_xp')
            ->groupBy('session_date')
            ->orderBy('session_date')
            ->get();

        $result = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $session = $sessions->firstWhere('session_date', $date);
            $result[] = [
                'date' => $date,
                'minutes' => $session ? $session->total_minutes : 0,
                'xp' => $session ? $session->total_xp : 0,
            ];
        }
        return $result;
    }

    public function getRecentActivities(int $userId, int $limit = 10): array {
        return LearningSession::where('user_id', $userId)
            ->with(['course', 'lesson'])
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'course_name' => $s->course->name ?? 'Unknown',
                'lesson_title' => $s->lesson?->title,
                'duration_minutes' => $s->duration_minutes,
                'xp_earned' => $s->xp_earned,
                'session_date' => $s->session_date->format('Y-m-d'),
                'completed' => $s->completed,
            ])
            ->toArray();
    }

    private function calculateStreak(int $userId): int {
        $sessions = LearningSession::where('user_id', $userId)
            ->selectRaw('session_date')
            ->distinct()
            ->orderByDesc('session_date')
            ->pluck('session_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        if (empty($sessions)) return 0;

        $streak = 0;
        $today = now()->format('Y-m-d');
        $checkDate = in_array($today, $sessions) ? $today : now()->subDay()->format('Y-m-d');

        foreach ($sessions as $date) {
            if ($date === $checkDate) {
                $streak++;
                $checkDate = \Carbon\Carbon::parse($checkDate)->subDay()->format('Y-m-d');
            } else {
                break;
            }
        }
        return $streak;
    }

    public function getAdminStats(): array {
        return [
            'total_users' => \App\Models\User::count(),
            'total_sessions' => LearningSession::count(),
            'total_courses' => \App\Models\Course::count(),
            'total_hours' => round(LearningSession::sum('duration_minutes') / 60, 1),
        ];
    }
}
