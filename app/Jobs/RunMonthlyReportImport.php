<?php

namespace App\Jobs;

use App\Imports\MonthlyReportImport;
use App\Models\ImportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class RunMonthlyReportImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $importId,
        public string $path,  // path on local disk, e.g. imports/xxx.xlsx
        public string $disk = 'local'
    ) {}

    public function handle(): void
    {
        $history = ImportHistory::where('import_id', $this->importId)->firstOrFail();

        $history->update([
            'status' => 'processing',
            'started_at' => now(),
            'error' => null,
        ]);

        try {
            // ให้ sheets เป็นตัว chunk (คุณใส่ WithChunkReading ใน sheets แล้ว)
            Excel::import(new MonthlyReportImport($this->importId), Storage::disk($this->disk)->path($this->path));

            // หมายเหตุ: rowCount จาก queued จะนับยาก (เพราะอยู่คนละ process)
            // ตั้งไว้ 0 หรือค่อยคำนวณจาก DB ทีหลัง
            $history->update([
                'status' => 'success',
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $history->update([
                'status' => 'error',
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }
}