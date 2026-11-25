<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Makes the first administrator. There is one administrator until that administrator grants the access to
 * someone else (Account administration → the account → Grant administrator access), so this command refuses
 * once an administrator exists.
 *
 * A promoted account sets its own administrator password at its first admin sign-in (/admin/login →
 * "Set up a newly granted administrator account" → emailed code → new password). A new account gets the password
 * typed here, or with --generate-password a strong random one that is shown once. Every admin sign-in also
 * needs a code emailed to the account.
 */
class MakeAdmin extends Command
{
    protected $signature = 'vakilneeti:make-admin {email? : The account email} {--yes : Promote an existing account without asking}
        {--name= : Name for a new administrator account} {--generate-password : Give a new administrator a strong random password, shown once}';

    protected $description = 'Create the first administrator or promote an existing user account';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) ($this->argument('email') ?: $this->ask('Administrator email'))));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address.');

            return self::FAILURE;
        }

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user?->is_admin) {
            $this->info('This account is already an administrator.');

            return self::SUCCESS;
        }

        if (User::query()->where('is_admin', true)->exists()) {
            $this->error('There is already an administrator. Only an administrator can give the access to another account: Account administration → the account → Grant administrator access.');

            return self::FAILURE;
        }

        if ($user) {

            if ($user->suspended_at || $user->isTeamMember()) {
                $this->error('A suspended account or a team member cannot be made an administrator.');

                return self::FAILURE;
            }

            $this->info('Existing account found: '.$user->name.' ('.$user->email.')');

            if (! $this->option('yes') && ! $this->confirm('Promote this existing account to administrator?')) {
                $this->warn('No changes made.');

                return self::SUCCESS;
            }

            $user->forceFill([
                'is_admin' => true,
                'admin_password_setup' => true,
                'admin_access_version' => (int) $user->admin_access_version + 1,
            ])->save();
            $this->audit($user, 'granted administrator access (command line)');

            $this->info('Promoted. Open /admin/login, choose "Set up a newly granted administrator account", and enter the code emailed to '.$user->email.' to set the administrator password.');

            return self::SUCCESS;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Administrator name')));

        if ($this->option('generate-password')) {
            $password = Str::password(20);
            $confirmation = $password;
        } else {
            $password = $this->secret('Set administrator password (at least 12 characters)');
            $confirmation = $this->secret('Confirm administrator password');
        }

        if (! $name || ! $password || mb_strlen($password) < 12 || $password !== $confirmation) {
            $this->error('Name is required, and the passwords must match and have at least 12 characters. No account created.');

            return self::FAILURE;
        }

        $user = User::create(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->forceFill(['is_admin' => true, 'password_set_at' => now(), 'email_verified_at' => now()])->save();
        $this->audit($user, 'created administrator (command line)');

        $this->info('Administrator account created. Sign in at /admin/login with this email and password; a code is emailed to '.$email.' each time.');
        if ($this->option('generate-password')) {
            $this->line('Password (shown only now, keep it safe): '.$password);
        }

        return self::SUCCESS;
    }

    private function audit(User $user, string $action): void
    {
        DB::table('admin_audit_entries')->insert(['actor_id' => $user->id, 'account_id' => $user->id, 'action' => $action, 'created_at' => now()]);
    }
}
