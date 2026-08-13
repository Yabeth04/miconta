<?php
namespace App\Jobs;

use App\Imports\AccountingMovementsImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportAccountingExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public string $importId,
        public string $disk,
        public string $path,
    ) {}

    public function handle(): void
    {
        Cache::put($this->cacheKey(), [
            'id'       => $this->importId,
            'status'   => 'processing',
            'progress' => 1,
            'total'    => 0,
            'imported' => 0,
            'message'  => 'Leyendo Excel...',
            'errors'   => [],
        ], now()->addHours(2));

        Excel::import(new AccountingMovementsImport($this->importId), $this->path, $this->disk);

        Storage::disk($this->disk)->delete($this->path);
    }

    public function failed(?Throwable $exception): void
    {
        Cache::put($this->cacheKey(), [
            'id'       => $this->importId,
            'status'   => 'failed',
            'progress' => 100,
            'total'    => 0,
            'imported' => 0,
            'message'  => 'Falló la importación: ' . ($exception?->getMessage() ?? 'error desconocido'),
            'errors'   => [$exception?->getMessage() ?? 'error desconocido'],
        ], now()->addHours(2));

        Storage::disk($this->disk)->delete($this->path);
    }

    private function cacheKey(): string
    {
        return "accounting-import:{$this->importId}";
    }
}
