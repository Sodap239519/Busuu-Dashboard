<?php
namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Course;
use App\Models\LearningSession;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProgress;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
    public function run(): void {
        // Create admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@busuu.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create regular users
        $users = [];
        $userData = [
            ['name' => 'Alice Johnson', 'email' => 'alice@busuu.test'],
            ['name' => 'Bob Smith', 'email' => 'bob@busuu.test'],
            ['name' => 'Carol Davis', 'email' => 'carol@busuu.test'],
            ['name' => 'David Wilson', 'email' => 'david@busuu.test'],
        ];

        foreach ($userData as $data) {
            $users[] = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'user',
            ]);
        }

        $courses = Course::all();

        // Create progress and sessions for each user
        foreach ($users as $user) {
            $userCourses = $courses->random(min(5, $courses->count()));
            foreach ($userCourses as $course) {
                $lessons = $course->lessons()->orderBy('order')->get();
                $completedCount = rand(1, max(1, $lessons->count()));
                $firstLesson = $lessons->first();
                $currentLesson = $lessons->get($completedCount - 1);

                UserProgress::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'lessons_completed' => $completedCount,
                    'total_lessons' => $lessons->count(),
                    'progress_percentage' => round($completedCount / max(1, $lessons->count()) * 100, 2),
                    'current_lesson_id' => $currentLesson?->id ?? $firstLesson?->id,
                    'started_at' => Carbon::now()->subDays(rand(10, 60)),
                ]);
            }

            // Create 50+ learning sessions over last 60 days
            for ($i = 0; $i < 60; $i++) {
                if (rand(0, 100) > 40) {
                    $course = $userCourses->random();
                    LearningSession::create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'duration_minutes' => rand(15, 90),
                        'xp_earned' => rand(25, 150),
                        'session_date' => Carbon::now()->subDays($i)->format('Y-m-d'),
                        'completed' => (bool) rand(0, 1),
                    ]);
                }
            }

            // Create achievements
            Achievement::create([
                'user_id' => $user->id,
                'type' => 'streak',
                'name' => '7-Day Streak',
                'description' => 'Studied for 7 days in a row',
                'icon' => '🔥',
                'earned_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);

            if (rand(0, 1)) {
                Achievement::create([
                    'user_id' => $user->id,
                    'type' => 'xp_milestone',
                    'name' => '1000 XP',
                    'description' => 'Earned 1000 XP total',
                    'icon' => '⭐',
                    'earned_at' => Carbon::now()->subDays(rand(1, 20)),
                ]);
            }
        }
    }
}
