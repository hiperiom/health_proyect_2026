<?php

use Illuminate\Support\Facades\Schema;

it('can migrate fresh with users profiles geolocation columns', function (): void {
    $this->artisan('migrate:fresh', ['--seed' => true])
        ->assertSuccessful();

    expect(Schema::hasTable('users_profiles'))->toBeTrue()
        ->and(Schema::hasColumn('users_profiles', 'state_id'))->toBeTrue()
        ->and(Schema::hasColumn('users_profiles', 'municipality_id'))->toBeTrue()
        ->and(Schema::hasColumn('users_profiles', 'address'))->toBeTrue();
});
