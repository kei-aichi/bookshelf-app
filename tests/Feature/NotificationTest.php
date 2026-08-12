<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分の通知のみ一覧に表示される
     */
    public function test_user_can_only_see_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '自分の通知タイトル',
                '自分の通知本文',
                'on_due_date'
            )
        );

        $otherUser->notify(
            new ReadingPlanReminderNotification(
                2,
                '他人の通知タイトル',
                '他人の通知本文',
                'on_due_date'
            )
        );

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertViewIs('notifications.index')
            ->assertSee('自分の通知タイトル')
            ->assertSee('自分の通知本文')
            ->assertDontSee('他人の通知タイトル')
            ->assertDontSee('他人の通知本文');
    }

    /**
     * 通知が0件の場合は所定のメッセージが表示される
     */
    public function test_notification_index_displays_empty_message_when_no_notifications_exist(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertSee('通知はありません。');
    }

    /**
     * 通知一覧は新しい通知から表示される
     */
    public function test_notifications_are_displayed_in_latest_order(): void
    {
        $user = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '古い通知',
                '古い通知本文',
                'three_days_before'
            )
        );

        $oldNotification = $user->notifications()->first();
        $oldNotification->created_at = now()->subDay();
        $oldNotification->save();

        $user->notify(
            new ReadingPlanReminderNotification(
                2,
                '新しい通知',
                '新しい通知本文',
                'on_due_date'
            )
        );

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '新しい通知',
                '古い通知',
            ]);
    }

    /**
     * 未読通知には未読表示と既読ボタンが表示される
     */
    public function test_unread_notification_displays_unread_badge_and_read_button(): void
    {
        $user = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '未読通知テスト',
                '未読通知の本文です。',
                'on_due_date'
            )
        );

        $notification = $user->notifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertSee('未読通知テスト')
            ->assertSee('未読通知の本文です。')
            ->assertSee('未読')
            ->assertSee('既読');
    }

    /**
     * 既読通知には未読表示と既読ボタンが表示されない
     */
    public function test_read_notification_does_not_display_unread_badge_or_read_button(): void
    {
        $user = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '確認済み通知テスト',
                '確認済み通知の本文です。',
                'on_due_date'
            )
        );

        $notification = $user->notifications()->first();

        $notification->markAsRead();

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertSee('確認済み通知テスト')
            ->assertSee('確認済み通知の本文です。')
            ->assertDontSee('未読')
            ->assertDontSee('既読');
    }

    /**
     * 自分の未読通知を既読にできる
     */
    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '既読処理テスト',
                '既読処理の本文です。',
                'on_due_date'
            )
        );

        $notification = $user->notifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this
            ->actingAs($user)
            ->post(route('notifications.read', $notification));

        $response
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', '通知が既読されました');

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    /**
     * 他ユーザーの通知を既読にできない
     */
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $owner->notify(
            new ReadingPlanReminderNotification(
                1,
                '他ユーザー通知',
                '他ユーザーの通知本文です。',
                'on_due_date'
            )
        );

        $notification = $owner->notifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this
            ->actingAs($otherUser)
            ->post(route('notifications.read', $notification));

        $response->assertForbidden();

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }

    /**
     * 3日前通知では3日前用の表示になる
     */
    public function test_three_days_before_notification_has_correct_display(): void
    {
        $user = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '3日前通知',
                '読書期日まであと3日です。',
                'three_days_before'
            )
        );

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertSee('3日前通知')
            ->assertSee('読書期日まであと3日です。')
            ->assertSee('bg-blue-500', false)
            ->assertSee('bg-blue-100', false)
            ->assertSee('text-blue-600', false);
    }

    /**
     * 当日通知では当日用の表示になる
     */
    public function test_on_due_date_notification_has_correct_display(): void
    {
        $user = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '当日通知',
                '読書期日は本日です。',
                'on_due_date'
            )
        );

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertSee('当日通知')
            ->assertSee('読書期日は本日です。')
            ->assertSee('bg-yellow-500', false)
            ->assertSee('bg-yellow-100', false)
            ->assertSee('text-yellow-700', false);
    }

    /**
     * 3日後通知では3日後用の表示になる
     */
    public function test_three_days_after_notification_has_correct_display(): void
    {
        $user = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '3日後通知',
                '読書期日から3日経過しました。',
                'three_days_after'
            )
        );

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertSee('3日後通知')
            ->assertSee('読書期日から3日経過しました。')
            ->assertSee('bg-red-500', false)
            ->assertSee('bg-red-100', false)
            ->assertSee('text-red-600', false);
    }

    /**
     * 通知の作成日時が相対日時で表示される
     */
    public function test_notification_created_at_is_displayed_as_relative_time(): void
    {
        Carbon::setTestNow('2026-08-13 12:00:00');

        $user = User::factory()->create();

        $user->notify(
            new ReadingPlanReminderNotification(
                1,
                '相対日時テスト',
                '相対日時確認用の通知です。',
                'on_due_date'
            )
        );

        $notification = $user->notifications()->first();

        $notification->created_at = Carbon::parse('2026-08-13 10:00:00');
        $notification->save();

        $response = $this
            ->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertSee('相対日時テスト')
            ->assertSee(
                $notification->fresh()->created_at->diffForHumans()
            );

        Carbon::setTestNow();
    }
}
