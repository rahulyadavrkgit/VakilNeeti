<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Make the site's push notification keys once, then put the two lines it prints into .env.
 */
class GenerateWebPushKeys extends Command
{
    protected $signature = 'webpush:keys';

    protected $description = 'Make the VAPID keys that sign push notifications (add the printed lines to .env)';

    public function handle(): int
    {
        $options = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $key = openssl_pkey_new($options);

        // Windows PHP builds may need their openssl.cnf named explicitly.
        foreach ([getenv('OPENSSL_CONF') ?: null, 'C:/php/extras/ssl/openssl.cnf'] as $config) {
            if (! $key && $config && is_file($config)) {
                $key = openssl_pkey_new($options + ['config' => $config]);
            }
        }

        if (! $key) {
            $this->error('OpenSSL could not make an EC key: '.openssl_error_string());

            return self::FAILURE;
        }

        $ec = openssl_pkey_get_details($key)['ec'];
        $encode = fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $pad = fn (string $value): string => str_pad($value, 32, "\x00", STR_PAD_LEFT);

        $this->line('VAPID_PUBLIC_KEY='.$encode("\x04".$pad($ec['x']).$pad($ec['y'])));
        $this->line('VAPID_PRIVATE_KEY='.$encode($pad($ec['d'])));

        return self::SUCCESS;
    }
}
