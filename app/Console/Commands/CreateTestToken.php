<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateTestToken extends Command
{
    protected $signature = 'app:create-test-token';

    protected $description = 'Create a Sanctum token for Postman testing';

    public function handle(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? User::whereHas('role', fn ($q) => $q->where('slug', 'super_admin'))->first()
            ?? User::first();

        if (! $user) {
            $this->error('No users found.');

            return;
        }

        $token = $user->createToken('postman-test')->plainTextToken;

        $this->info("User: {$user->name} (ID: {$user->id}, Role: {$user->role->slug})");
        $this->info("Token: {$token}");
    }
}
