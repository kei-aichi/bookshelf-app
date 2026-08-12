<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReadingPlanRemindersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト終了後に固定した現在日時を解除する
     */
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 進行中の読書計画で期日3日前の場合は通知が作成される
     */
    public function test_notification_is_created_three_days_before_due_date(): void
    {
        Carbon::setTestNow('2026-08-13');

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '3日前通知テスト',
        ]);

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Reading,
            'target_date' => '2026-08-16',
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertSame(
            'three_days_before',
            $notification->data['timing']
        );

        $this->assertSame(
            '読書計画の期日が近づいています',
            $notification->data['title']
        );

        $this->assertSame(
            '「3日前通知テスト」の読書期日まであと3日です。',
            $notification->data['body']
        );
    }

    /**
     * 進行中の読書計画で期日当日の場合は通知が作成される
     */
    public function test_notification_is_created_on_due_date(): void
    {
        Carbon::setTestNow('2026-08-13');

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '当日通知テスト',
        ]);

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Reading,
            'target_date' => '2026-08-13',
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertSame(
            'on_due_date',
            $notification->data['timing']
        );

        $this->assertSame(
            '今日は読書計画の期日です',
            $notification->data['title']
        );

        $this->assertSame(
            '「当日通知テスト」の読書期日は本日です。',
            $notification->data['body']
        );
    }

    /**
     * 進行中の読書計画で期日3日後の場合は通知が作成される
     */
    public function test_notification_is_created_three_days_after_due_date(): void
    {
        Carbon::setTestNow('2026-08-13');

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '3日後通知テスト',
        ]);

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Reading,
            'target_date' => '2026-08-10',
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );

        $this->assertSame(
            'three_days_after',
            $notification->data['timing']
        );

        $this->assertSame(
            '読書計画の期日を過ぎています',
            $notification->data['title']
        );

        $this->assertSame(
            '「3日後通知テスト」の読書期日から3日経過しました。',
            $notification->data['body']
        );
    }

    /**
     * 通知対象日以外では通知が作成されない
     */
    public function test_notification_is_not_created_outside_reminder_timings(): void
    {
        Carbon::setTestNow('2026-08-13');

        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Reading,
            'target_date' => '2026-08-15',
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertSame(
            0,
            $user->notifications()->count()
        );
    }

    /**
     * 開始前の読書計画には通知を作成しない
     */
    public function test_notification_is_not_created_for_not_started_plan(): void
    {
        Carbon::setTestNow('2026-08-13');

        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::NotStarted,
            'target_date' => '2026-08-13',
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertSame(
            0,
            $user->notifications()->count()
        );
    }

    /**
     * 読了済みの読書計画には通知を作成しない
     */
    public function test_notification_is_not_created_for_completed_plan(): void
    {
        Carbon::setTestNow('2026-08-13');

        $user = User::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'target_date' => '2026-08-13',
            'completed_at' => '2026-08-12',
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->assertSame(
            0,
            $user->notifications()->count()
        );
    }

    /**
     * 同じ計画・同じタイミングの通知は重複作成されない
     */
    public function test_same_notification_is_not_created_twice(): void
    {
        Carbon::setTestNow('2026-08-13');

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '重複防止テスト',
        ]);

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Reading,
            'target_date' => '2026-08-16',
        ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $this->artisan('reading-plans:send-reminders')
            ->assertSuccessful();

        $count = $user->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', 'three_days_before')
            ->count();

        $this->assertSame(1, $count);
    }

    /**
     * 読書計画リマインダーが毎日9時に実行されるようスケジュール登録されている
     */
    public function test_reading_plan_reminder_is_scheduled_daily_at_nine(): void
    {
        $events = app(Schedule::class)->events();

        $event = collect($events)->first(function ($event) {
            return str_contains(
                $event->command,
                'reading-plans:send-reminders'
            );
        });

        $this->assertNotNull($event);

        $this->assertSame(
            '0 9 * * *',
            $event->expression
        );
    }
}
