<?php

use App\Models\ApiClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('api:token:create {name : Human-readable application/environment name}', function (): int {
    $name = trim((string) $this->argument('name'));

    if ($name === '') {
        $this->error('The API client name cannot be empty.');

        return 1;
    }

    if (ApiClient::query()->where('name', $name)->exists()) {
        $this->error("An API client named [{$name}] already exists.");

        return 1;
    }

    $plainTextToken = 'cae_'.Str::random(64);

    $client = ApiClient::query()->create([
        'name' => $name,
        'token_hash' => hash('sha256', $plainTextToken),
        'is_active' => true,
    ]);

    $this->info("Created API client #{$client->id}: {$client->name}");
    $this->newLine();
    $this->line('Token:');
    $this->line($plainTextToken);
    $this->newLine();
    $this->warn('Save this token now. Only its hash is stored and the plaintext cannot be shown again.');

    return 0;
})->purpose('Create a permanent bearer token for an API client');

Artisan::command('api:token:list', function (): void {
    $clients = ApiClient::query()
        ->orderBy('id')
        ->get();

    $this->table(
        ['ID', 'Name', 'Status', 'Created', 'Last used'],
        $clients->map(fn (ApiClient $client): array => [
            $client->id,
            $client->name,
            $client->is_active ? 'active' : 'revoked',
            $client->created_at?->format('Y-m-d H:i:s') ?? '-',
            $client->last_used_at?->format('Y-m-d H:i:s') ?? 'never',
        ])->all(),
    );
})->purpose('List API clients and token status');

Artisan::command('api:token:revoke {id : API client ID}', function (): int {
    $client = ApiClient::query()->find($this->argument('id'));

    if (! $client) {
        $this->error('API client not found.');

        return 1;
    }

    if (! $client->is_active) {
        $this->warn("API client #{$client->id} is already revoked.");

        return 0;
    }

    $client->forceFill(['is_active' => false])->save();

    $this->info("Revoked token for API client #{$client->id}: {$client->name}");

    return 0;
})->purpose('Revoke an API client token permanently');
