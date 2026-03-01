<?php
namespace App\Http\Controllers;

use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller {
    public function __construct(private StatisticsService $statsService) {}

    public function index(Request $request) {
        return Inertia::render('Executive/Dashboard', [
            'stats' => $this->statsService->getExecutiveStats(),
            'weeklyActivity' => $this->statsService->getExecutiveWeeklyActivity(30),
            'recentActivities' => $this->statsService->getExecutiveRecentActivities(10),
            'achievements' => $this->statsService->getExecutiveAchievements(12),
            'courses' => $this->statsService->getExecutiveTopCourses(8),
        ]);
    }
}