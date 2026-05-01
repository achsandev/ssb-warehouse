<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class ApiClientCreate extends Command
{
    protected $signature = 'api-client:create
        {name : Nama aplikasi partner (mis. "PT Partner A - Procurement")}
        {--email= : Email PIC partner}
        {--phone= : Nomor telepon PIC}
        {--pic= : Nama PIC}
        {--rate-limit=60 : Rate limit per menit (default 60)}
        {--notes= : Catatan internal}
        {--url= : URL aplikasi partner (informatif saja)}';

    protected $description = 'Daftarkan aplikasi partner baru sebagai API client.';

    public function handle(): int
    {
        $client = ApiClient::create([
            'name' => $this->argument('name'),
            'contact_email' => $this->option('email'),
            'contact_phone' => $this->option('phone'),
            'pic_name' => $this->option('pic'),
            'rate_limit_per_minute' => (int) $this->option('rate-limit'),
            'notes' => $this->option('notes'),
            'application_url' => $this->option('url'),
            'is_active' => true,
        ]);

        $this->info('API client created.');
        $this->table(
            ['Field', 'Value'],
            [
                ['UID', $client->uid],
                ['Name', $client->name],
                ['Rate limit/min', $client->rate_limit_per_minute],
                ['Active', $client->is_active ? 'yes' : 'no'],
            ]
        );

        $this->line('');
        $this->comment('Next steps:');
        $this->line("  1. php artisan api-client:add-ip {$client->uid} <cidr>");
        $this->line("  2. php artisan api-client:issue-token {$client->uid} --abilities=items:read,item-requests:create");

        return self::SUCCESS;
    }
}
