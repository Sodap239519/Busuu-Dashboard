<?php
namespace App\Services;

use App\Models\Achievement;
use App\Models\Course;
use App\Models\LearningSession;
use App\Models\UserProgress;

class StatisticsService {

    // ===== Existing user-scoped methods remain =====

    /**
     * Anchor date for executive dashboard.
     * Using "now()" makes charts empty when imported data is old (e.g. Nov 2025).
     */
    private function executiveAnchorDate(): \Carbon\Carbon
    {
        $maxDate = LearningSession::max('session_date');
        return $maxDate ? \Carbon\Carbon::parse($maxDate) : now();
    }

    public function getExecutiveStats(): array
    {
        $totalMinutes = (int) LearningSession::sum('duration_minutes');
        $totalXp = (int) LearningSession::sum('xp_earned');

        return [
            'total_hours' => round($totalMinutes / 60, 1),
            'total_xp' => $totalXp,
            'courses_count' => (int) Course::count(),
            // completed_courses นิยามแบบ platform-wide: จำนวน progress ที่ 100%
            'completed_courses' => (int) UserProgress::where('progress_percentage', 100)->count(),
            // streak ไม่ meaningful แบบทั้งระบบ -> ตั้ง 0 หรือค่อยทำ metric อื่น
            'streak_days' => 0,
        ];
    }

    public function getExecutiveWeeklyActivity(int $days = 30): array
    {
        $anchor = $this->executiveAnchorDate();

        $from = $anchor->copy()->subDays($days - 1)->format('Y-m-d');
        $to = $anchor->copy()->format('Y-m-d');

        $sessions = LearningSession::query()
            ->whereBetween('session_date', [$from, $to])
            ->selectRaw('session_date, SUM(duration_minutes) as total_minutes, SUM(xp_earned) as total_xp')
            ->groupBy('session_date')
            ->orderBy('session_date')
            ->get();

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $anchor->copy()->subDays($i)->format('Y-m-d');
            $row = $sessions->firstWhere('session_date', $date);

            $result[] = [
                'date' => $date,
                'minutes' => $row ? (int) $row->total_minutes : 0,
                'xp' => $row ? (int) $row->total_xp : 0,
            ];
        }

        return $result;
    }

    public function getExecutiveRecentActivities(int $limit = 10): array
    {
        return LearningSession::query()
            ->with(['user', 'course', 'lesson'])
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'user_name' => $s->user?->name ?? 'Unknown',
                'course_name' => $s->course?->name ?? 'Unknown',
                'lesson_title' => $s->lesson?->title,
                'duration_minutes' => $s->duration_minutes,
                'xp_earned' => $s->xp_earned,
                'session_date' => $s->session_date->format('Y-m-d'),
                'completed' => $s->completed,
            ])
            ->toArray();
    }

    public function getExecutiveAchievements(int $limit = 12): array
    {
        return Achievement::query()
            ->with('user')
            ->orderByDesc('earned_at')
            ->limit($limit)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'name' => $a->name,
                'description' => $a->description,
                'icon' => $a->icon,
                'earned_at' => optional($a->earned_at)->toISOString(),
                'user_name' => $a->user?->name ?? 'Unknown',
            ])
            ->toArray();
    }

    public function getExecutiveTopCourses(int $limit = 8): array
    {
        // Top courses by total minutes (platform-wide)
        $top = LearningSession::query()
            ->selectRaw('course_id, SUM(duration_minutes) as total_minutes, SUM(xp_earned) as total_xp, COUNT(*) as sessions_count')
            ->whereNotNull('course_id')
            ->groupBy('course_id')
            ->orderByDesc('total_minutes')
            ->limit($limit)
            ->get();

        $courseIds = $top->pluck('course_id')->all();
        $courses = \App\Models\Course::whereIn('id', $courseIds)->get()->keyBy('id');

        return $top->map(fn($row) => [
            'id' => $row->course_id,
            'name' => $courses[$row->course_id]->name ?? 'Unknown',
            'language' => $courses[$row->course_id]->language ?? null,
            'level' => $courses[$row->course_id]->level ?? null,
            'icon' => $courses[$row->course_id]->icon ?? '📚',
            'color' => $courses[$row->course_id]->color ?? '#3B82F6',
            'total_minutes' => (int) $row->total_minutes,
            'total_xp' => (int) $row->total_xp,
            'sessions_count' => (int) $row->sessions_count,
        ])->toArray();
    }
}