<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('wholesale:token {user : User ID or email} {name=wholesale-client} {--abilities=wholesale.read,products.read,manufacturers.read,categories.read,products.prices.read,products.quantities.read} {--expires=}', function (): int {
    if (! app(\App\Services\Catalog\CatalogFeatureService::class)->useApi()) {
        $this->error('Wholesale API is disabled in Catalog Features.');
        return self::FAILURE;
    }

    $selector = (string) $this->argument('user');
    $tokenName = trim((string) $this->argument('name'));
    $abilitiesRaw = trim((string) $this->option('abilities'));
    $expiresRaw = trim((string) $this->option('expires'));

    $user = User::query()
        ->when(ctype_digit($selector), fn ($query) => $query->where('id', (int) $selector), fn ($query) => $query->where('email', $selector))
        ->first();

    if (! $user) {
        $this->error('User not found.');
        return self::FAILURE;
    }
    if (! (bool) ($user->api_access_enabled ?? false)) {
        $this->error('User API access is disabled. Enable it in Settings > API first.');
        return self::FAILURE;
    }

    $abilities = collect(explode(',', $abilitiesRaw))
        ->map(fn ($ability) => trim((string) $ability))
        ->filter(fn ($ability) => $ability !== '')
        ->values()
        ->all();

    if ($abilities === []) {
        $this->error('At least one ability is required.');
        return self::FAILURE;
    }

    $expiresAt = null;
    if ($expiresRaw !== '') {
        try {
            $expiresAt = CarbonImmutable::parse($expiresRaw);
        } catch (\Throwable) {
            $this->error('Invalid --expires value. Use a parseable datetime, e.g. "2026-12-31 23:59:59".');
            return self::FAILURE;
        }
    }

    $token = $user->createToken($tokenName, $abilities, $expiresAt);

    $this->info('Token created.');
    $this->line('User: '.$user->id.' <'.$user->email.'>');
    $this->line('Name: '.$tokenName);
    $this->line('Abilities: '.implode(', ', $abilities));
    if ($expiresAt) {
        $this->line('Expires at: '.$expiresAt->toDateTimeString());
    }
    $this->newLine();
    $this->warn('Plain token (copy now):');
    $this->line($token->plainTextToken);

    return self::SUCCESS;
})->purpose('Create a wholesale API token for a user');
