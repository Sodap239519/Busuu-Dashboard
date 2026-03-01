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

    public function __construct()
    {
        $this->completionSheet  = new CourseCompletionRateSheet();
        $this->progressSheet    = new StudentProgressSheet();
        $this->placementSheet   = new AchievementSheet('Placement Test');
        $this->certificateSheet = new AchievementSheet('Certificate');
    }

    /**
     * Map Busuu monthly report sheet names to their respective import classes.
     * Maatwebsite/Excel matches sheet names case-insensitively.
     */
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
