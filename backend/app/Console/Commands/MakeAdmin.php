<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'vakilneeti:make-admin {email?}';

    protected $description = 'Create an administrator or promote an existing user account';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Administrator email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address.');
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->is_admin) {
                $this->info('This account is already an administrator.');
                return self::SUCCESS;
            }

            $this->info('Existing account found: ' . $user->name . ' (' . $user->email . ')');

            if (!$this->confirm('Promote this existing account to administrator?')) {
                $this->warn('No changes made.');
                return self::SUCCESS;
            }

            $user->forceFill(['is_admin' => true])->save();

            $this->info('Existing account promoted to administrator.');
            return self::SUCCESS;
        }

        $name = $this->ask('Administrator name');
        $password = $this->secret('Set administrator password');
        $confirmation = $this->secret('Confirm administrator password');

        if (!$name || !$password || $password !== $confirmation) {
            $this->error('Name is required and passwords must match. No account created.');
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->forceFill(['is_admin' => true])->save();

        $this->info('Administrator account created successfully.');
        return self::SUCCESS;
    }
}