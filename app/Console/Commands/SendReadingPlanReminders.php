<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    /**
     * コンソールコマンド名
     */
    protected $signature = 'reading-plans:send-reminders';

    /**
     * コマンド説明
     */
    protected $description = '読書計画のリマインダー通知を送信する';

    /**
     * コマンドを実行する。
     */
    public function handle(): int
    {
        $today = Carbon::today();

        $readingPlans = ReadingPlan::query()
            ->where('status', ReadingPlanStatus::Reading)
            ->with(['user', 'book'])
            ->get();

        $readingPlans->each(function (ReadingPlan $readingPlan) use ($today): void {
            $targetDate = Carbon::parse($readingPlan->target_date);

            $diffDays = $today->diffInDays($targetDate, false);

            if ($diffDays === 3) {
                $timing = 'three_days_before';

                if (! $this->notificationExists($readingPlan, $timing)) {
                    $readingPlan->user->notify(
                        new ReadingPlanReminderNotification(
                            $readingPlan->id,
                            '読書計画の期日が近づいています',
                            "「{$readingPlan->book->title}」の読書期日まであと3日です。",
                            $timing
                        )
                    );
                }
            }

            if ($diffDays === 0) {
                $timing = 'on_due_date';

                if (! $this->notificationExists($readingPlan, $timing)) {
                    $readingPlan->user->notify(
                        new ReadingPlanReminderNotification(
                            $readingPlan->id,
                            '今日は読書計画の期日です',
                            "「{$readingPlan->book->title}」の読書期日は本日です。",
                            $timing
                        )
                    );
                }
            }

            if ($diffDays === -3) {
                $timing = 'three_days_after';

                if (! $this->notificationExists($readingPlan, $timing)) {
                    $readingPlan->user->notify(
                        new ReadingPlanReminderNotification(
                            $readingPlan->id,
                            '読書計画の期日を過ぎています',
                            "「{$readingPlan->book->title}」の読書期日から3日経過しました。",
                            $timing
                        )
                    );
                }
            }
        });

        $this->info('読書計画リマインダーを実行しました。');

        return self::SUCCESS;
    }

    /**
     * 同じ通知が既に存在するか確認する。
     */
    private function notificationExists(
        ReadingPlan $readingPlan,
        string $timing
    ): bool {
        return $readingPlan->user
            ->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', $timing)
            ->exists();
    }
}
