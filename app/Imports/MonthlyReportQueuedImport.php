<?php

namespace App\Imports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class MonthlyReportQueuedImport extends MonthlyReportImport implements ShouldQueue, WithChunkReading
{
    public function chunkSize(): int
    {
        // เลขนี้ใช้แค่ให้ผ่าน constraint ของ queued import
        // ตัวอ่านจริงเป็นแต่ละ sheet ที่ chunk อยู่แล้ว
        return 1000;
    }
}