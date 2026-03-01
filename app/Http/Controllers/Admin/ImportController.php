<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\CoursesImport;
use App\Imports\LearningSessionsImport;
use App\Imports\MonthlyReportImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller {
    public function index() {
        $history = $this->getImportHistory();
        return Inertia::render('Admin/Import', ['history' => $history]);
    }

    public function upload(Request $request) {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'type' => 'required|in:sessions,courses,monthly_report',
        ]);

        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        try {
            if ($request->type === 'sessions') {
                $import = new LearningSessionsImport();
                Excel::import($import, $file);
                $rowCount = $import->rowCount;
                $message = "Successfully imported {$rowCount} learning sessions.";
            } elseif ($request->type === 'monthly_report') {
                $import = new MonthlyReportImport();
                Excel::import($import, $file);
                $rowCount = $import->getRowCount();
                $message = "Successfully imported {$rowCount} records from monthly report.";
            } else {
                $import = new CoursesImport();
                Excel::import($import, $file);
                $rowCount = $import->rowCount;
                $message = "Successfully imported {$rowCount} courses.";
            }

            $this->logImport($file->getClientOriginalName(), $request->type, $rowCount, 'success');

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $this->logImport($file->getClientOriginalName(), $request->type, 0, 'error', $e->getMessage());
            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    private function logImport(string $filename, string $type, int $rows, string $status, string $error = ''): void {
        $history = $this->getImportHistory();
        array_unshift($history, [
            'filename' => $filename,
            'type' => $type,
            'rows' => $rows,
            'status' => $status,
            'error' => $error,
            'imported_at' => now()->toISOString(),
        ]);
        Storage::disk('local')->put('imports/history.json', json_encode(array_slice($history, 0, 50)));
    }

    private function getImportHistory(): array {
        if (Storage::disk('local')->exists('imports/history.json')) {
            return json_decode(Storage::disk('local')->get('imports/history.json'), true) ?? [];
        }
        return [];
    }
}
