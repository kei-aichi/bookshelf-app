<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly int $readingPlanId,
        private readonly string $title,
        private readonly string $body,
        private readonly string $timing,
    ) {}

    /**
     * 配信チャネル
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * DBへ保存するデータ
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlanId,
            'title' => $this->title,
            'body' => $this->body,
            'timing' => $this->timing,
        ];
    }
}
