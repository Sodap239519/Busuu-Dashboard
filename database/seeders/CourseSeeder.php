<?php
namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder {
    public function run(): void {
        $courses = [
            ['name' => 'English A1', 'language' => 'English', 'level' => 'A1', 'description' => 'Beginner English', 'total_lessons' => 20, 'estimated_hours' => 10, 'icon' => '🇬🇧', 'color' => '#3B82F6'],
            ['name' => 'English A2', 'language' => 'English', 'level' => 'A2', 'description' => 'Elementary English', 'total_lessons' => 25, 'estimated_hours' => 15, 'icon' => '🇬🇧', 'color' => '#2563EB'],
            ['name' => 'English B1', 'language' => 'English', 'level' => 'B1', 'description' => 'Intermediate English', 'total_lessons' => 30, 'estimated_hours' => 20, 'icon' => '🇬🇧', 'color' => '#1D4ED8'],
            ['name' => 'Spanish A1', 'language' => 'Spanish', 'level' => 'A1', 'description' => 'Beginner Spanish', 'total_lessons' => 20, 'estimated_hours' => 10, 'icon' => '🇪🇸', 'color' => '#EF4444'],
            ['name' => 'Spanish A2', 'language' => 'Spanish', 'level' => 'A2', 'description' => 'Elementary Spanish', 'total_lessons' => 25, 'estimated_hours' => 15, 'icon' => '🇪🇸', 'color' => '#DC2626'],
            ['name' => 'French A1', 'language' => 'French', 'level' => 'A1', 'description' => 'Beginner French', 'total_lessons' => 20, 'estimated_hours' => 10, 'icon' => '🇫🇷', 'color' => '#8B5CF6'],
            ['name' => 'French B1', 'language' => 'French', 'level' => 'B1', 'description' => 'Intermediate French', 'total_lessons' => 30, 'estimated_hours' => 20, 'icon' => '🇫🇷', 'color' => '#7C3AED'],
            ['name' => 'German A1', 'language' => 'German', 'level' => 'A1', 'description' => 'Beginner German', 'total_lessons' => 22, 'estimated_hours' => 12, 'icon' => '🇩🇪', 'color' => '#F59E0B'],
            ['name' => 'Italian A1', 'language' => 'Italian', 'level' => 'A1', 'description' => 'Beginner Italian', 'total_lessons' => 18, 'estimated_hours' => 9, 'icon' => '🇮🇹', 'color' => '#10B981'],
            ['name' => 'Japanese A1', 'language' => 'Japanese', 'level' => 'A1', 'description' => 'Beginner Japanese', 'total_lessons' => 25, 'estimated_hours' => 15, 'icon' => '🇯🇵', 'color' => '#EC4899'],
            ['name' => 'Korean A1', 'language' => 'Korean', 'level' => 'A1', 'description' => 'Beginner Korean', 'total_lessons' => 20, 'estimated_hours' => 12, 'icon' => '🇰🇷', 'color' => '#06B6D4'],
            ['name' => 'Portuguese A1', 'language' => 'Portuguese', 'level' => 'A1', 'description' => 'Beginner Portuguese', 'total_lessons' => 18, 'estimated_hours' => 10, 'icon' => '🇵🇹', 'color' => '#84CC16'],
        ];

        foreach ($courses as $courseData) {
            $course = Course::create($courseData);
            for ($i = 1; $i <= $course->total_lessons; $i++) {
                Lesson::create([
                    'course_id' => $course->id,
                    'title' => "Lesson {$i}: " . $this->getLessonTitle($i),
                    'order' => $i,
                    'type' => ['video', 'quiz', 'exercise', 'reading'][($i - 1) % 4],
                    'duration_minutes' => [15, 20, 25, 30][($i - 1) % 4],
                ]);
            }
        }
    }

    private function getLessonTitle(int $num): string {
        $titles = ['Greetings', 'Numbers', 'Colors', 'Food & Drink', 'Family', 'Days & Time', 'Weather', 'Travel', 'Shopping', 'Work', 'Health', 'Hobbies', 'Nature', 'Culture', 'Technology', 'Sports', 'Music', 'Art', 'Science', 'History', 'Geography', 'Literature', 'Business', 'Social', 'Review'];
        return $titles[($num - 1) % count($titles)];
    }
}
