<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\LearningSession;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private StatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StatisticsService::class);
    }

    // -------------------------------------------------------------------------
    // getLicenceMetrics
    // -------------------------------------------------------------------------

    public function test_licence_metrics_counts_roster_users(): void
    {
        User::factory()->create(['busuu_status' => 'Active']);
        User::factory()->create(['busuu_status' => 'Active']);
        User::factory()->create(['busuu_status' => 'Pending']);
        User::factory()->create(['busuu_status' => null]); // not in roster

        $metrics = $this->service->getLicenceMetrics();

        $this->assertSame(3, $metrics['total']);
        $this->assertSame(2, $metrics['active']);
        $this->assertSame(1, $metrics['pending']);
    }

    public function test_licence_metrics_zero_when_no_roster(): void
    {
        $metrics = $this->service->getLicenceMetrics();

        $this->assertSame(0, $metrics['total']);
        $this->assertSame(0, $metrics['active']);
        $this->assertSame(0, $metrics['pending']);
    }

    // -------------------------------------------------------------------------
    // getWeeklyActiveLearnersPct
    // -------------------------------------------------------------------------

    public function test_wal_pct_returns_zero_with_no_licences(): void
    {
        $this->assertSame(0.0, $this->service->getWeeklyActiveLearnersPct());
    }

    public function test_wal_pct_computed_from_learning_sessions(): void
    {
        $users  = User::factory()->count(4)->create(['busuu_status' => 'Active']);
        $course = \App\Models\Course::create([
            'name' => 'Test', 'language' => 'en', 'level' => 'B1',
        ]);

        // 2 of 4 users have sessions in the last 7 days
        $recentDate = now()->format('Y-m-d');
        LearningSession::create([
            'user_id' => $users[0]->id, 'course_id' => $course->id,
            'session_date' => $recentDate, 'duration_minutes' => 30,
            'xp_earned' => 10, 'completed' => true,
        ]);
        LearningSession::create([
            'user_id' => $users[1]->id, 'course_id' => $course->id,
            'session_date' => $recentDate, 'duration_minutes' => 20,
            'xp_earned' => 5, 'completed' => true,
        ]);

        $pct = $this->service->getWeeklyActiveLearnersPct();
        $this->assertSame(50.0, $pct);
    }

    // -------------------------------------------------------------------------
    // getTotalLearningHours
    // -------------------------------------------------------------------------

    public function test_total_learning_hours_converts_minutes(): void
    {
        $user   = User::factory()->create();
        $course = \App\Models\Course::create([
            'name' => 'Test', 'language' => 'en', 'level' => 'B1',
        ]);
        LearningSession::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'session_date' => now()->format('Y-m-d'),
            'duration_minutes' => 90, 'xp_earned' => 0, 'completed' => false,
        ]);
        LearningSession::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'session_date' => now()->format('Y-m-d'),
            'duration_minutes' => 30, 'xp_earned' => 0, 'completed' => false,
        ]);

        $this->assertSame(2.0, $this->service->getTotalLearningHours());
    }

    // -------------------------------------------------------------------------
    // getTotalLessonsCompleted
    // -------------------------------------------------------------------------

    public function test_total_lessons_completed_sums_progress(): void
    {
        $user   = User::factory()->create();
        $course = \App\Models\Course::create([
            'name' => 'Test', 'language' => 'en', 'level' => 'A1',
        ]);
        UserProgress::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lessons_completed' => 10, 'total_lessons' => 20, 'progress_percentage' => 50,
        ]);
        UserProgress::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lessons_completed' => 5, 'total_lessons' => 20, 'progress_percentage' => 25,
        ]);

        $this->assertSame(15, $this->service->getTotalLessonsCompleted());
    }

    // -------------------------------------------------------------------------
    // getCertificatesByCEFR
    // -------------------------------------------------------------------------

    public function test_certificates_by_cefr_returns_all_levels(): void
    {
        $cefr = $this->service->getCertificatesByCEFR();

        foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $level) {
            $this->assertArrayHasKey($level, $cefr);
            $this->assertSame(0, $cefr[$level]);
        }
    }

    public function test_certificates_by_cefr_counts_achievements(): void
    {
        $user = User::factory()->create();
        // Importer stores name='Certificate' type='completion' description='Completed: B2 English'
        Achievement::create([
            'user_id' => $user->id, 'type' => 'completion',
            'name' => 'Certificate', 'description' => 'Completed: B2 English',
            'earned_at' => now(),
        ]);
        Achievement::create([
            'user_id' => $user->id, 'type' => 'completion',
            'name' => 'Certificate', 'description' => 'Completed: B2 Advanced',
            'earned_at' => now(),
        ]);
        Achievement::create([
            'user_id' => $user->id, 'type' => 'completion',
            'name' => 'Certificate', 'description' => 'Completed: A1 Starter',
            'earned_at' => now(),
        ]);
        // Placement test achievement – should not count
        Achievement::create([
            'user_id' => $user->id, 'type' => 'completion',
            'name' => 'Placement Test', 'description' => 'Completed: B2 Placement',
            'earned_at' => now(),
        ]);

        $cefr = $this->service->getCertificatesByCEFR();

        $this->assertSame(1, $cefr['A1']);
        $this->assertSame(2, $cefr['B2']);
        $this->assertSame(0, $cefr['B1']);
    }

    // -------------------------------------------------------------------------
    // getTeamsReport
    // -------------------------------------------------------------------------

    public function test_teams_report_aggregates_per_team(): void
    {
        User::factory()->create(['busuu_status' => 'Active',  'team' => 'Alpha']);
        User::factory()->create(['busuu_status' => 'Active',  'team' => 'Alpha']);
        User::factory()->create(['busuu_status' => 'Pending', 'team' => 'Alpha']);
        User::factory()->create(['busuu_status' => 'Active',  'team' => 'Beta']);

        $report = $this->service->getTeamsReport();

        $alpha = collect($report)->firstWhere('team', 'Alpha');
        $this->assertNotNull($alpha);
        $this->assertSame(3, $alpha['total']);
        $this->assertSame(2, $alpha['active']);
        $this->assertSame(1, $alpha['pending']);
        $this->assertSame(round(2 / 3 * 100, 1), $alpha['active_ratio']);

        $beta = collect($report)->firstWhere('team', 'Beta');
        $this->assertNotNull($beta);
        $this->assertSame(1, $beta['total']);
        $this->assertSame(100.0, $beta['active_ratio']);
    }

    // -------------------------------------------------------------------------
    // getStudentsReport
    // -------------------------------------------------------------------------

    public function test_students_report_excludes_non_roster_users(): void
    {
        User::factory()->create(['busuu_status' => 'Active',  'team' => 'Alpha']);
        User::factory()->create(['busuu_status' => null]);   // not imported

        $students = $this->service->getStudentsReport();

        $this->assertCount(1, $students);
    }

    public function test_students_report_filter_by_status(): void
    {
        User::factory()->create(['busuu_status' => 'Active',  'team' => 'A']);
        User::factory()->create(['busuu_status' => 'Pending', 'team' => 'B']);

        $active = $this->service->getStudentsReport(['status' => 'Active']);
        $this->assertCount(1, $active);
        $this->assertSame('Active', $active[0]['status']);

        $pending = $this->service->getStudentsReport(['status' => 'Pending']);
        $this->assertCount(1, $pending);
        $this->assertSame('Pending', $pending[0]['status']);
    }

    public function test_students_report_filter_by_team(): void
    {
        User::factory()->create(['busuu_status' => 'Active', 'team' => 'Alpha']);
        User::factory()->create(['busuu_status' => 'Active', 'team' => 'Beta']);

        $result = $this->service->getStudentsReport(['team' => 'Alpha']);
        $this->assertCount(1, $result);
        $this->assertSame('Alpha', $result[0]['team']);
    }

    // -------------------------------------------------------------------------
    // getFilterOptions
    // -------------------------------------------------------------------------

    public function test_filter_options_returns_distinct_teams_and_faculties(): void
    {
        User::factory()->create(['busuu_status' => 'Active',  'team' => 'Zeta', 'faculty' => 'Sci']);
        User::factory()->create(['busuu_status' => 'Pending', 'team' => 'Alpha', 'faculty' => 'Arts']);
        User::factory()->create(['busuu_status' => 'Active',  'team' => 'Alpha', 'faculty' => 'Sci']);

        $opts = $this->service->getFilterOptions();

        $this->assertSame(['Active', 'Pending'], $opts['statuses']);
        $this->assertSame(['Alpha', 'Zeta'], $opts['teams']);
        $this->assertSame(['Arts', 'Sci'], $opts['faculties']);
    }

    // -------------------------------------------------------------------------
    // getMeetingInsights
    // -------------------------------------------------------------------------

    public function test_meeting_insights_structure(): void
    {
        $insights = $this->service->getMeetingInsights();

        $this->assertArrayHasKey('top_pending',  $insights);
        $this->assertArrayHasKey('top_active',   $insights);
        $this->assertArrayHasKey('zero_lessons', $insights);
        $this->assertArrayHasKey('by_faculty',   $insights);
        $this->assertArrayHasKey('trend',        $insights);
    }

    public function test_zero_lessons_includes_active_users_without_progress(): void
    {
        $userNoProgress = User::factory()->create(['busuu_status' => 'Active']);
        $userWithLesson = User::factory()->create(['busuu_status' => 'Active']);
        $course = \App\Models\Course::create([
            'name' => 'Test', 'language' => 'en', 'level' => 'A1',
        ]);
        UserProgress::create([
            'user_id' => $userWithLesson->id, 'course_id' => $course->id,
            'lessons_completed' => 3, 'total_lessons' => 10, 'progress_percentage' => 30,
        ]);

        $insights = $this->service->getMeetingInsights();
        $emails   = array_column($insights['zero_lessons'], 'email');

        $this->assertContains($userNoProgress->email, $emails);
        $this->assertNotContains($userWithLesson->email, $emails);
    }
}
