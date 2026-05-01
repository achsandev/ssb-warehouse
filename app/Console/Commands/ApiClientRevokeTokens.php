<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class ApiClientRevokeTokens extends Command
{
    protected $signature = 'api-client:revoke-all-tokens
        {uid : UID api_client}
        {--force : Skip confirmation prompt}';

    protected $description = 'Revoke SEMUA token milik api_client (emergency). Client masih ada, tinggal issue token baru.';

    public function handle(): int
    {
        $client = ApiClient::where('uid', $this->argument('uid'))->first();
        if (! $client) {
            $this->error('ApiClient not found for uid '.$this->argument('uid'));
            return self::FAILURE;
        }

        if (! $this->option('force')
            && ! $this->confirm("Revoke ALL tokens for '{$client->name}' ({$client->uid})?", false)) {
            $this->line('Aborted.');
            return self::SUCCESS;
        }

        $deleted = $client->tokens()->delete();

        $this->info("Revoked {$deleted} token(s) for client {$client->uid}.");
        return self::SUCCESS;
    }
}
