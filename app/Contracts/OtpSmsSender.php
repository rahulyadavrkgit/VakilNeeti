<?php

namespace App\Contracts;

/**
 * Sends a sign-in or sign-up code by SMS. VakilNiti makes the code and checks it itself (only a hash is
 * stored); the provider (AuthKey.io) only delivers it. Not used until phone sign-in is set up.
 */
interface OtpSmsSender
{
    /**
     * Send the code to the mobile number (stored as +91XXXXXXXXXX). Returns null when it was sent,
     * or a message to show the person when it could not be.
     */
    public function sendOtp(string $mobile, string $code): ?string;
}
