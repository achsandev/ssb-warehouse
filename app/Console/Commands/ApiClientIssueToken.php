<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class ApiClientIssueToken extends Command
{
    protected $signature = 'api-client:issue-token
        {uid : UID api_client}
        {--name=default : Nama token (untuk identifikasi di dashboard)}
        {--abilities= : Comma-separated abilities (mis. items:read,item-requests:create). Kosong = ["*"]}
        {--expires= : Expiry date YYYY-MM-DD (kosong = 1 tahun dari sekarang)}';

    protected $description = 'Terbitkan Sanctum PAT baru untuk api_client. Plain token hanya ditampilkan SEKALI.';

    public function handle(): int
    {
        $client = ApiClient::where('uid', $this->argument('uid'))->first();
        if (! $client) {
            $this->error('ApiClient not found for uid '.$this->argument('uid'));
            return self::FAILURE;
        }

        $abilitiesOpt = (string) $this->option('abilities');
        $abilities = $abilitiesOpt === ''
            ? ['*']
            : array_values(array_filter(array_map('trim', explode(',', $abilitiesOpt))));

        $expiresOpt = $this->option('expires');
        $expiresAt = $expiresOpt
            ? \Carbon\Carbon::parse($expiresOpt)->endOfDay()
            : now()->addYear();

        $newToken = $client->createToken(
            $this->option('name'),
            $abilities,
            $expiresAt
        );

        $this->line('');
        $this->line('╔══════════════════════════════════════════════════════════════╗');
        $this->line('║  TOKEN ISSUED — shown ONCE. Copy now.                        ║');
        $this->line('╠══════════════════════════════════════════════════════════════╣');
        $this->line('║  Client   : '.str_pad($client->name, 49).'║');
        $this->line('║  UID      : '.str_pad($client->uid, 49).'║');
        $this->line('║  Name     : '.str_pad($this->option('name'), 49).'║');
        $this->line('║  Expires  : '.str_pad((string) $expiresAt, 49).'║');
        $this->line('║  Abilities: '.str_pad(implode(',', $abilities), 49).'║');
        $this->line('╚══════════════════════════════════════════════════════════════╝');
        $this->line('');
        $this->info('Plain token:');
        $this->line('  '.$newToken->plainTextToken);
        $this->line('');
        $this->warn('Send this token to the partner via a secure channel (1Password share, OneTimeSecret, PGP email). Never email/chat it in plain text.');

        return self::SUCCESS;
    }
}
