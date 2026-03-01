<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImportHistoryService
{
    public function add(array $entry): void
    {
        $history = $this->all();
        array_unshift($history, $entry);
        Storage::disk('local')->put('imports/history.json', json_encode(array_slice($history, 0, 50)));
    }

    public function update(string $importId, array $patch): void
    {
        $history = $this->all();

        foreach ($history as $i => $item) {
            if (($item['import_id'] ?? null) === $importId) {
                $history[$i] = array_merge($item, $patch);
                break;
            }
        }

        Storage::disk('local')->put('imports/history.json', json_encode(array_slice($history, 0, 50)));
    }

    public function all(): array
    {
        if (!Storage::disk('local')->exists('imports/history.json')) {
            return [];
        }

        return json_decode(Storage::disk('local')->get('imports/history.json'), true) ?? [];
    }
}