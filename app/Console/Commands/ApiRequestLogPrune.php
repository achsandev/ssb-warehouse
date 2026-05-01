<?php

namespace App\Console\Commands;

use App\Models\ApiRequestLog;
use Illuminate\Console\Command;

class ApiRequestLogPrune extends Command
{
    protected $signature = 'api:prune-logs
        {--days=90 : Hapus log yang lebih tua dari N hari (default 90)}';

    protected $description = 'Hapus audit log API yang umurnya > N hari. Dijadwalkan otomatis di routes/console.php.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        // Hapus batch-wise untuk menghindari deadlock pada tabel besar.
        $total = 0;
        do {
            $deleted = ApiRequestLog::where('created_at', '<', $cutoff)
                ->limit(5000)
                ->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->info("Pruned {$total} log row(s) older than {$cutoff}.");

        return self::SUCCESS;
    }
}
