<?php

namespace App\Console\Commands;

use App\Services\Msg91EmailService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Checks the MSG91 email set-up: sends a sample sign-in code (123456) to the given address.
 */
#[Signature('app:test-login-email {email : Where to send the sample code}')]
#[Description('Send a sample sign-in code email through MSG91 to check the set-up')]
class TestLoginEmail extends Command
{
    public function handle(Msg91EmailService $mailer): int
    {
        if (! $mailer->isConfigured()) {
            $this->error('MSG91 email is not set up. Fill MSG91_AUTH_KEY, MSG91_EMAIL_DOMAIN, MSG91_EMAIL_FROM and MSG91_EMAIL_OTP_TEMPLATE in .env, then run php artisan config:clear.');

            return self::FAILURE;
        }

        $error = $mailer->sendOtp((string) $this->argument('email'), '123456', 'Test');

        if ($error !== null) {
            $this->error($error.' The reason MSG91 gave is in the newest file in storage/logs.');

            return self::FAILURE;
        }

        $this->info('Sent. A sample code 123456 should arrive at '.$this->argument('email').' (check spam too).');

        return self::SUCCESS;
    }
}
