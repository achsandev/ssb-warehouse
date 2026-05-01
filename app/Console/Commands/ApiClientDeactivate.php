<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class ApiClientDeactivate extends Command
{
    protected $signature = 'api-client:deactivate
        {uid : UID api_client}
        {--activate : Aktifkan kembali (alih-alih nonaktifkan)}';

    protected $description = 'Nonaktifkan api_client (is_active=false). Token tetap ada, request langsung 403. Bisa di-aktifkan lagi.';

    public function handle(): int
    {
        $client = ApiClient::where('uid', $this->argument('uid'))->first();
        if (! $client) {
            $this->error('ApiClient not found for uid '.$this->argument('uid'));
            return self::FAILURE;
        }

        $client->update(['is_active' => (bool) $this->option('activate')]);

        $state = $client->is_active ? 'ACTIVATED' : 'DEACTIVATED';
        $this->info("Client {$client->uid} is now {$state}.");
        return self::SUCCESS;
    }
}
