<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\WebPush;
use Illuminate\Notifications\Notification;

/**
 * Every alert also reaches the person's phones and computers that turned on push notifications.
 * The alert itself is the one saved for the Alerts page; the push only says that it is there.
 */
class WebPushChannel
{
    public function __construct(private WebPush $push) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! $this->push->isConfigured()) {
            return;
        }

        foreach ($notifiable->pushSubscriptions()->get() as $subscription) {
            $this->push->ping($subscription);
        }
    }
}
