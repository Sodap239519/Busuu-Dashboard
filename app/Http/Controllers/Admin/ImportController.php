<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\CoursesImport;
use App\Imports\LearningSessionsImport;
use App\Jobs\RunMonthlyReportImport;
use App\Models\ImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function index()
    {
        $history = ImportHistory::query()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($h) => [
                'import_id' => $h->import_id,
                'filename' => $h->filename,
                'type' => $h->type,
                'rows' => (int) $h->rows,
                'status' => $h->status,
                'error' => $h->error,
                'imported_at' => optional($h->created_at)->toISOString(),
                'started_at' => optional($h->started_at)->toISOString(),
                'finished_at' => optional($h->finished_at)->toISOString(),
            ])
            ->all();

        return Inertia::render('Admin/Import', ['history' => $history]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'type' => 'nullable|in:sessions,courses,monthly_report',
        ]);

        $type = $request->input('type') ?: 'monthly_report';

        $originalName = $request->file('file')->getClientOriginalName();
        $path = $request->file('file')->store('imports', 'local');

        // สร้าง record ประวัติ
        $importId = (string) Str::uuid();

        ImportHistory::create([
            'import_id' => $importId,
            'filename' => $originalName,
            'type' => $type,
            'rows' => 0,
            'status' => 'queued',
        ]);

        try {
            if ($type === 'monthly_report') {
                RunMonthlyReportImport::dispatch($importId, $path, 'local');

                return back()->with('success', 'Upload received. Import is running in background.');
            }

            if ($type === 'sessions') {
                $import = new LearningSessionsImport();
                Excel::import($import, Storage::disk('local')->path($path));
                $rowCount = (int) ($import->rowCount ?? 0);

                ImportHistory::where('import_id', $importId)->update([
                    'rows' => $rowCount,
                    'status' => 'success',
                    'started_at' => now(),
                    'finished_at' => now(),
                ]);

                return back()->with('success', "Successfully imported {$rowCount} learning sessions.");
            }

            // courses
            $import = new CoursesImport();
            Excel::import($import, Storage::disk('local')->path($path));
            $rowCount = (int) ($import->rowCount ?? 0);

            ImportHistory::where('import_id', $importId)->update([
                'rows' => $rowCount,
                'status' => 'success',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return back()->with('success', "Successfully imported {$rowCount} courses.");
        } catch (\Throwable $e) {
            ImportHistory::where('import_id', $importId)->update([
                'status' => 'error',
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}