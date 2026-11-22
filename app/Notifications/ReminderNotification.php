<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * An in-app reminder shown under the header bell (hearing, task, appointment or overdue invoice).
 */
class ReminderNotification extends Notification
{
    /**
     * @param  string  $kind  hearing|task|appointment|invoice
     * @param  string  $key  identifies the reminder so it is sent only once
     */
    public function __construct(
        public string $kind,
        public string $title,
        public string $body,
        public string $url,
        public string $key,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{kind: string, title: string, body: string, url: string, key: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'key' => $this->key,
        ];
    }
}
