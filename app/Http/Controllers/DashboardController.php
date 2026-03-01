<?php
namespace App\Http\Controllers;

use App\Models\UserProgress;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller {
    public function __construct(private StatisticsService $statsService) {}

    public function index(Request $request) {
        $user = $request->user();
        $stats = $this->statsService->getUserStats($user->id);
        $weeklyActivity = $this->statsService->getWeeklyActivity($user->id);
        $recentActivities = $this->statsService->getRecentActivities($user->id);

        $courses = UserProgress::where('user_id', $user->id)
            ->with(['course', 'currentLesson'])
            ->get()
            ->map(fn($p) => [
                'id' => $p->course->id,
                'name' => $p->course->name,
                'language' => $p->course->language,
                'level' => $p->course->level,
                'icon' => $p->course->icon,
                'color' => $p->course->color,
                'progress' => $p->progress_percentage,
                'lessons_completed' => $p->lessons_completed,
                'total_lessons' => $p->total_lessons,
                'next_lesson' => $p->currentLesson?->title,
            ]);

        $achievements = $user->achievements()->orderByDesc('earned_at')->get();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'weeklyActivity' => $weeklyActivity,
            'recentActivities' => $recentActivities,
            'courses' => $courses,
            'achievements' => $achievements,
        ]);
    }
}
