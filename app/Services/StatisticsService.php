<?php
namespace App\Services;

use App\Models\Achievement;
use App\Models\Course;
use App\Models\LearningSession;
use App\Models\User;
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

    // =========================================================================
    // Executive Dashboard – roster-based metrics (PR #5 roster fields)
    // =========================================================================

    /** Licence counts: total (roster), active, pending. */
    public function getLicenceMetrics(): array
    {
        $total   = User::whereNotNull('busuu_status')->count();
        $active  = User::where('busuu_status', 'Active')->count();
        $pending = User::where('busuu_status', 'Pending')->count();
        return ['total' => $total, 'active' => $active, 'pending' => $pending];
    }

    /**
     * Weekly Active Learners %: distinct users with learning_sessions in the
     * 7-day window ending at the anchor date, divided by total roster licences.
     */
    public function getWeeklyActiveLearnersPct(): float
    {
        $total = User::whereNotNull('busuu_status')->count();
        if ($total === 0) {
            return 0.0;
        }
        $anchor = $this->executiveAnchorDate();
        $from   = $anchor->copy()->subDays(6)->format('Y-m-d');
        $to     = $anchor->copy()->format('Y-m-d');

        $row = LearningSession::whereDate('session_date', '>=', $from)
            ->whereDate('session_date', '<=', $to)
            ->selectRaw('COUNT(DISTINCT user_id) as cnt')
            ->first();
        $activeCount = (int) ($row?->cnt ?? 0);

        return round($activeCount / $total * 100, 1);
    }

    /** Total learning hours across all sessions. */
    public function getTotalLearningHours(): float
    {
        return round((int) LearningSession::sum('duration_minutes') / 60, 1);
    }

    /** Total lessons completed across all user_progress rows. */
    public function getTotalLessonsCompleted(): int
    {
        return (int) UserProgress::sum('lessons_completed');
    }

    /**
     * Count certificate achievements (name = 'Certificate') grouped by CEFR level.
     * The CEFR level is detected from the description field set by the importer
     * ('Completed: {course_name}') or the achievement name itself.
     */
    public function getCertificatesByCEFR(): array
    {
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1'];
        $result = [];
        foreach ($levels as $level) {
            $result[$level] = Achievement::where('name', 'Certificate')
                ->where(function ($q) use ($level) {
                    $q->where('description', 'like', "%{$level}%")
                      ->orWhere('name', 'like', "%{$level}%");
                })
                ->count();
        }
        return $result;
    }

    // =========================================================================
    // Teams report
    // =========================================================================

    /** Per-team aggregates: total, active, pending, active_ratio. */
    public function getTeamsReport(): array
    {
        return User::whereNotNull('busuu_status')
            ->selectRaw("team,
                COUNT(*) as total,
                SUM(CASE WHEN busuu_status = 'Active'  THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN busuu_status = 'Pending' THEN 1 ELSE 0 END) as pending_count")
            ->groupBy('team')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'team'         => $row->team ?? 'Unknown',
                'total'        => (int) $row->total,
                'active'       => (int) $row->active_count,
                'pending'      => (int) $row->pending_count,
                'active_ratio' => $row->total > 0
                    ? round($row->active_count / $row->total * 100, 1)
                    : 0.0,
            ])
            ->toArray();
    }

    // =========================================================================
    // Students report
    // =========================================================================

    /**
     * List students from the roster with optional filters.
     * Filters: status (Active|Pending), team, faculty.
     */
    public function getStudentsReport(array $filters = []): array
    {
        $query = User::whereNotNull('busuu_status')
            ->select([
                'id', 'email', 'name', 'team', 'faculty', 'major',
                'busuu_user_group', 'busuu_status', 'busuu_name_en',
                'busuu_name_th', 'last_imported_at',
            ]);

        if (!empty($filters['status'])) {
            $query->where('busuu_status', $filters['status']);
        }
        if (!empty($filters['team'])) {
            $query->where('team', $filters['team']);
        }
        if (!empty($filters['faculty'])) {
            $query->where('faculty', $filters['faculty']);
        }

        return $query->orderBy('email')->get()->map(fn ($u) => [
            'id'               => $u->id,
            'email'            => $u->email,
            'name'             => $u->busuu_name_en ?? $u->name,
            'team'             => $u->team,
            'faculty'          => $u->faculty,
            'major'            => $u->major,
            'busuu_user_group' => $u->busuu_user_group,
            'status'           => $u->busuu_status,
            'last_imported_at' => optional($u->last_imported_at)->toISOString(),
        ])->toArray();
    }

    // =========================================================================
    // Meeting insights
    // =========================================================================

    public function getMeetingInsights(): array
    {
        return [
            'top_pending'  => $this->topTeamsByStatus('Pending', 5),
            'top_active'   => $this->topTeamsByStatus('Active', 5),
            'zero_lessons' => $this->activeUsersWithZeroLessons(50),
            'by_faculty'   => $this->usageByFaculty(),
            'trend'        => $this->getWeeklyTrend(12),
        ];
    }

    /** Top $limit teams by count of users with given busuu_status. */
    private function topTeamsByStatus(string $status, int $limit): array
    {
        $col = strtolower($status) . '_count';
        return User::whereNotNull('busuu_status')
            ->selectRaw("team, SUM(CASE WHEN busuu_status = ? THEN 1 ELSE 0 END) as {$col}", [$status])
            ->groupBy('team')
            ->orderByDesc($col)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['team' => $r->team ?? 'Unknown', 'count' => (int) $r->{$col}])
            ->toArray();
    }

    /** Active-status users who have completed 0 lessons across all progress rows. */
    private function activeUsersWithZeroLessons(int $limit): array
    {
        return User::where('busuu_status', 'Active')
            ->whereDoesntHave('progress', fn ($q) => $q->where('lessons_completed', '>', 0))
            ->select(['id', 'email', 'name', 'busuu_name_en', 'team'])
            ->limit($limit)
            ->get()
            ->map(fn ($u) => [
                'email' => $u->email,
                'name'  => $u->busuu_name_en ?? $u->name,
                'team'  => $u->team,
            ])
            ->toArray();
    }

    /** % of active users per faculty. */
    private function usageByFaculty(): array
    {
        return User::whereNotNull('busuu_status')
            ->selectRaw("faculty,
                COUNT(*) as total,
                SUM(CASE WHEN busuu_status = 'Active' THEN 1 ELSE 0 END) as active_count")
            ->groupBy('faculty')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'faculty'   => $r->faculty ?? 'Unknown',
                'total'     => (int) $r->total,
                'active'    => (int) $r->active_count,
                'usage_pct' => $r->total > 0
                    ? round($r->active_count / $r->total * 100, 1)
                    : 0.0,
            ])
            ->toArray();
    }

    /**
     * Weekly trend for the last $weeks weeks ending at the anchor date.
     * Returns: week (end date), wal_pct, hours.
     */
    private function getWeeklyTrend(int $weeks): array
    {
        $anchor        = $this->executiveAnchorDate();
        $totalLicences = User::whereNotNull('busuu_status')->count();

        $result = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekEnd   = $anchor->copy()->subWeeks($i)->format('Y-m-d');
            $weekStart = $anchor->copy()->subWeeks($i)->subDays(6)->format('Y-m-d');

            $row = LearningSession::whereDate('session_date', '>=', $weekStart)
                ->whereDate('session_date', '<=', $weekEnd)
                ->selectRaw('COUNT(DISTINCT user_id) as cnt')
                ->first();
            $walCount = (int) ($row?->cnt ?? 0);

            $walPct = $totalLicences > 0
                ? round($walCount / $totalLicences * 100, 1)
                : 0.0;

            $minutes = (int) LearningSession::whereDate('session_date', '>=', $weekStart)
                ->whereDate('session_date', '<=', $weekEnd)
                ->sum('duration_minutes');

            $result[] = [
                'week'    => $weekEnd,
                'wal_pct' => $walPct,
                'hours'   => round($minutes / 60, 1),
            ];
        }
        return $result;
    }

    // =========================================================================
    // Filter option helpers
    // =========================================================================

    /** Distinct non-null values for status / team / faculty filter dropdowns. */
    public function getFilterOptions(): array
    {
        return [
            'statuses'  => ['Active', 'Pending'],
            'teams'     => User::whereNotNull('team')->distinct()->orderBy('team')->pluck('team')->toArray(),
            'faculties' => User::whereNotNull('faculty')->distinct()->orderBy('faculty')->pluck('faculty')->toArray(),
        ];
    }

    public function getExecutiveWeeklyActivity(int $days = 30): array
    {
        $anchor = $this->executiveAnchorDate();

        $from = $anchor->copy()->subDays($days - 1)->format('Y-m-d');
        $to = $anchor->copy()->format('Y-m-d');

        $sessions = LearningSession::query()
            ->whereDate('session_date', '>=', $from)
            ->whereDate('session_date', '<=', $to)
            ->selectRaw('DATE(session_date) as session_date, SUM(duration_minutes) as total_minutes, SUM(xp_earned) as total_xp')
            ->groupByRaw('DATE(session_date)')
            ->orderByRaw('DATE(session_date)')
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