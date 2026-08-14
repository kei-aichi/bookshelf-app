<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationPolicy
{
    /**
     * 通知を既読にできるか判定する。
     */
    public function read(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === User::class
            && $notification->notifiable_id === $user->id;
    }
}
