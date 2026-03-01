<?php
namespace App\Imports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class CoursesImport implements ToModel, WithHeadingRow, SkipsOnError {
    use SkipsErrors;

    public int $rowCount = 0;

    public function model(array $row): ?Course {
        $this->rowCount++;
        return new Course([
            'name' => $row['name'] ?? '',
            'language' => $row['language'] ?? '',
            'level' => $row['level'] ?? 'A1',
            'description' => $row['description'] ?? null,
            'total_lessons' => (int) ($row['total_lessons'] ?? 0),
            'estimated_hours' => (float) ($row['estimated_hours'] ?? 0),
            'icon' => $row['icon'] ?? null,
            'color' => $row['color'] ?? '#3B82F6',
        ]);
    }
}
