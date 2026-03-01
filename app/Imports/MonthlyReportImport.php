<?php

namespace App\Imports;

use App\Imports\Sheets\AchievementSheet;
use App\Imports\Sheets\CourseCompletionRateSheet;
use App\Imports\Sheets\StudentProgressSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonthlyReportImport implements WithMultipleSheets
{
    private CourseCompletionRateSheet $completionSheet;
    private StudentProgressSheet $progressSheet;
    private AchievementSheet $placementSheet;
    private AchievementSheet $certificateSheet;

    public function __construct(private string $importId)
    {
        $this->completionSheet  = new CourseCompletionRateSheet($this->importId);
        $this->progressSheet    = new StudentProgressSheet($this->importId);
        $this->placementSheet   = new AchievementSheet('Placement Test', $this->importId);
        $this->certificateSheet = new AchievementSheet('Certificate', $this->importId);
    }

    public function sheets(): array
    {
        return [
            'course-completion-rate'      => $this->completionSheet,
            'Student-Progress-Report'     => $this->progressSheet,
            'achievement-Placement test'  => $this->placementSheet,
            'achievement-Certificate'     => $this->certificateSheet,
        ];
    }

    public function getRowCount(): int
    {
        return $this->completionSheet->rowCount
            + $this->progressSheet->rowCount
            + $this->placementSheet->rowCount
            + $this->certificateSheet->rowCount;
    }
}