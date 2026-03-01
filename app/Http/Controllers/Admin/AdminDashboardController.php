<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use App\Models\LearningSession;
use Inertia\Inertia;

class AdminDashboardController extends Controller {
    public function __construct(private StatisticsService $statsService) {}

    public function index() {
        $stats = $this->statsService->getAdminStats();
        $recentSessions = LearningSession::with(['user', 'course'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'user_name' => $s->user->name,
                'course_name' => $s->course->name,
                'duration_minutes' => $s->duration_minutes,
                'xp_earned' => $s->xp_earned,
                'session_date' => $s->session_date->format('Y-m-d'),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentSessions' => $recentSessions,
        ]);
    }
}
