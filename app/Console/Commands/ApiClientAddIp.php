<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class ApiClientAddIp extends Command
{
    protected $signature = 'api-client:add-ip
        {uid : UID api_client}
        {cidr : IP atau CIDR (mis. 203.0.113.5 atau 10.0.0.0/24)}
        {--label= : Label deskriptif (mis. "Production egress")}';

    protected $description = 'Tambah IP/CIDR ke allowlist api_client.';

    public function handle(): int
    {
        $client = ApiClient::where('uid', $this->argument('uid'))->first();
        if (! $client) {
            $this->error('ApiClient not found for uid '.$this->argument('uid'));
            return self::FAILURE;
        }

        $cidr = trim((string) $this->argument('cidr'));

        // Auto-append /32 untuk IPv4 tunggal, /128 untuk IPv6 tunggal,
        // supaya admin tidak perlu hafal notasi CIDR.
        if (! str_contains($cidr, '/')) {
            $cidr .= str_contains($cidr, ':') ? '/128' : '/32';
        }

        [$ip] = explode('/', $cidr, 2);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error("Invalid IP/CIDR: {$cidr}");
            return self::FAILURE;
        }

        $row = $client->allowedIps()->updateOrCreate(
            ['cidr' => $cidr],
            [
                'label' => $this->option('label'),
                'is_active' => true,
            ]
        );

        $this->info("Allowed IP {$row->cidr} attached to client {$client->uid}.");
        return self::SUCCESS;
    }
}
