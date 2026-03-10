<?php

namespace Ddk\Api\Console;

use Illuminate\Console\Command;
use Backend\Models\User as BackendUser;
use Illuminate\Support\Str;

/**
 * Generate API Token Command
 *
 * Creates a new API token for a backend user. The token is displayed
 * once and then stored hashed in the database.
 *
 * Usage: php artisan api:generate-token <username>
 */
class GenerateTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'api:generate-token
                            {username : The backend username to generate token for}
                            {--revoke : Revoke existing token instead of generating new one}';

    /**
     * The console command description.
     */
    protected $description = 'Generate or revoke an API token for a backend user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');

        $user = BackendUser::where('login', $username)->first();

        if (!$user) {
            $this->error("User '{$username}' not found.");
            return 1;
        }

        if ($this->option('revoke')) {
            return $this->revokeToken($user);
        }

        return $this->generateToken($user);
    }

    /**
     * Generate a new token for the user
     */
    protected function generateToken(BackendUser $user): int
    {
        // Generate a random token
        $token = Str::random(64);

        // Hash it for storage
        $hashedToken = hash('sha256', $token);

        // Save to user
        $user->api_token = $hashedToken;
        $user->save();

        $this->info('');
        $this->info('API token generated successfully!');
        $this->info('');
        $this->warn('IMPORTANT: Save this token now. It will not be shown again.');
        $this->info('');
        $this->line('Token: ' . $token);
        $this->info('');
        $this->info('Use this token in the Authorization header:');
        $this->line('Authorization: Bearer ' . $token);
        $this->info('');

        return 0;
    }

    /**
     * Revoke the user's token
     */
    protected function revokeToken(BackendUser $user): int
    {
        if (!$user->api_token) {
            $this->warn("User '{$user->login}' has no API token.");
            return 0;
        }

        $user->api_token = null;
        $user->save();

        $this->info("API token revoked for user '{$user->login}'.");

        return 0;
    }
}
