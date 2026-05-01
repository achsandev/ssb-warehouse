<?php

use App\Models\ApiClient;

/**
 * Feature test middleware chain ApiClient pada route nyata `/api/items`:
 *
 *   throttle:60,1 → auth:sanctum → api_client.active → api_client.origin
 *   → ability:items:read
 *
 * Pakai route real supaya test menggambarkan flow produksi.
 */
function makeApiClient(array $overrides = []): ApiClient
{
    return ApiClient::create(array_merge([
        'name'           => 'Test Partner',
        'url'            => 'https://partner.example.com',
        'is_active'      => true,
        'enforce_origin' => true,
    ], $overrides));
}

it('menolak request tanpa bearer token dengan 401', function () {
    $this->getJson('/api/items')
        ->assertStatus(401);
});

it('meloloskan request dengan token valid, Origin cocok, dan ability cocok', function () {
    $client = makeApiClient();
    $token = $client->createToken('test', ['items:read'])->plainTextToken;

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Origin'        => 'https://partner.example.com',
    ])
        ->getJson('/api/items')
        ->assertOk();
});

it('menolak 403 jika ApiClient nonaktif', function () {
    $client = makeApiClient(['is_active' => false]);
    $token = $client->createToken('test', ['items:read'])->plainTextToken;

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Origin'        => 'https://partner.example.com',
    ])
        ->getJson('/api/items')
        ->assertStatus(403)
        ->assertJsonPath('message', 'API client is disabled.');
});

it('menolak 403 jika enforce_origin true dan Origin header tidak ada', function () {
    $client = makeApiClient();
    $token = $client->createToken('test', ['items:read'])->plainTextToken;

    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->getJson('/api/items')
        ->assertStatus(403)
        ->assertJsonPath('message', 'Request Origin does not match the registered URL for this API client.');
});

it('menolak 403 jika Origin host tidak match url terdaftar', function () {
    $client = makeApiClient();
    $token = $client->createToken('test', ['items:read'])->plainTextToken;

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Origin'        => 'https://attacker.example.com',
    ])
        ->getJson('/api/items')
        ->assertStatus(403)
        ->assertJsonPath('message', 'Request Origin does not match the registered URL for this API client.');
});

it('lolos saat enforce_origin = false meski Origin tidak ada', function () {
    $client = makeApiClient(['enforce_origin' => false]);
    $token = $client->createToken('test', ['items:read'])->plainTextToken;

    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->getJson('/api/items')
        ->assertOk();
});

it('menolak 403 jika token tidak punya ability yang dibutuhkan', function () {
    $client = makeApiClient();
    $token = $client->createToken('test', ['other:scope'])->plainTextToken;

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Origin'        => 'https://partner.example.com',
    ])
        ->getJson('/api/items')
        ->assertStatus(403);
});

it('originMatches case-insensitive pada hostname', function () {
    $client = makeApiClient(['url' => 'https://Partner.Example.COM']);
    expect($client->originMatches('https://partner.example.com'))->toBeTrue();
    expect($client->originMatches('https://PARTNER.example.com:8080'))->toBeTrue();
    expect($client->originMatches('https://other.example.com'))->toBeFalse();
});

it('originMatches mengabaikan port dan path saat compare', function () {
    $client = makeApiClient(['url' => 'https://app.example.com']);
    expect($client->originMatches('https://app.example.com:443'))->toBeTrue();
    expect($client->originMatches('https://app.example.com'))->toBeTrue();
});
