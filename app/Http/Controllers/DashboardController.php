<?php
namespace App\Http\Controllers;

use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller {
    public function __construct(private StatisticsService $statsService) {}

    public function index(Request $request) {
        return Inertia::render('Executive/Dashboard', [
            // Legacy activity chart data
            'weeklyActivity'  => $this->statsService->getExecutiveWeeklyActivity(30),
            // Section 1 – Overall metrics
            'licences'        => $this->statsService->getLicenceMetrics(),
            'walPct'          => $this->statsService->getWeeklyActiveLearnersPct(),
            'totalHours'      => $this->statsService->getTotalLearningHours(),
            'totalLessons'    => $this->statsService->getTotalLessonsCompleted(),
            'cefr'            => $this->statsService->getCertificatesByCEFR(),
            // Section 2 – Teams
            'teamsReport'     => $this->statsService->getTeamsReport(),
            // Section 3 – Students
            'studentsReport'  => $this->statsService->getStudentsReport(),
            // Section 5 – Meeting insights
            'meetingInsights' => $this->statsService->getMeetingInsights(),
            // Filter dropdowns
            'filterOptions'   => $this->statsService->getFilterOptions(),
        ]);
    }

    /** AJAX endpoint: re-fetch students with filters. */
    public function studentsJson(Request $request) {
        $filters = $request->only(['status', 'team', 'faculty']);
        return response()->json($this->statsService->getStudentsReport($filters));
    }

    /** CSV export endpoint. Type: students | teams | progress | achievements | credit. */
    public function exportCsv(Request $request, string $type) {
        $filename = $type . '_report_' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $rows = match ($type) {
            'students'     => $this->csvStudents($request),
            'teams'        => $this->csvTeams(),
            'progress'     => $this->csvProgress(),
            'achievements' => $this->csvAchievements(),
            'credit'       => $this->csvCreditStatus(),
            default        => [],
        };

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // -------------------------------------------------------------------------
    // CSV helpers
    // -------------------------------------------------------------------------

    private function csvStudents(Request $request): array
    {
        $filters  = $request->only(['status', 'team', 'faculty']);
        $students = $this->statsService->getStudentsReport($filters);
        $rows     = [['Email', 'Name', 'Team', 'Faculty', 'Major', 'Group', 'Status', 'Last Imported']];
        foreach ($students as $s) {
            $rows[] = [
                $s['email'], $s['name'], $s['team'], $s['faculty'],
                $s['major'], $s['busuu_user_group'], $s['status'], $s['last_imported_at'],
            ];
        }
        return $rows;
    }

    private function csvTeams(): array
    {
        $teams = $this->statsService->getTeamsReport();
        $rows  = [['Team', 'Total', 'Active', 'Pending', 'Active %']];
        foreach ($teams as $t) {
            $rows[] = [$t['team'], $t['total'], $t['active'], $t['pending'], $t['active_ratio']];
        }
        return $rows;
    }

    private function csvProgress(): array
    {
        $rows = [['User', 'Email', 'Team', 'Course', 'Lessons Completed', 'Total Lessons', 'Progress %']];
        $data = \App\Models\UserProgress::with(['user', 'course'])
            ->orderBy('user_id')->get();
        foreach ($data as $p) {
            $rows[] = [
                $p->user?->name, $p->user?->email, $p->user?->team,
                $p->course?->name, $p->lessons_completed,
                $p->total_lessons, $p->progress_percentage,
            ];
        }
        return $rows;
    }

    private function csvAchievements(): array
    {
        $rows = [['User', 'Email', 'Type', 'Name', 'Description', 'Earned At']];
        $data = \App\Models\Achievement::with('user')->orderByDesc('earned_at')->get();
        foreach ($data as $a) {
            $rows[] = [
                $a->user?->name, $a->user?->email,
                $a->type, $a->name, $a->description,
                optional($a->earned_at)->toISOString(),
            ];
        }
        return $rows;
    }

    private function csvCreditStatus(): array
    {
        $licences = $this->statsService->getLicenceMetrics();
        $rows = [
            ['Metric', 'Count'],
            ['Total Licences',  $licences['total']],
            ['Active (In Use)', $licences['active']],
            ['Pending',         $licences['pending']],
        ];
        return $rows;
    }
}