<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザー自身の読書計画のみ表示される
     */
    public function test_user_can_only_see_their_own_reading_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownBook = Book::factory()->create([
            'title' => '自分の読書計画',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '他人の読書計画',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $ownBook->id,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response
            ->assertOk()
            ->assertSee('自分の読書計画')
            ->assertDontSee('他人の読書計画');
    }

    /**
     * 読書計画が0件の場合は所定のメッセージが表示される
     */
    public function test_reading_plan_index_displays_empty_message_when_no_plans_exist(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response
            ->assertOk()
            ->assertSee('該当する読書計画はありません。');
    }

    /**
     * 状態で読書計画を絞り込める
     */
    public function test_reading_plans_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();

        $notStartedBook = Book::factory()->create([
            'title' => '開始前の本',
        ]);

        $readingBook = Book::factory()->create([
            'title' => '進行中の本',
        ]);

        $completedBook = Book::factory()->create([
            'title' => '読了済みの本',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $notStartedBook->id,
            'status' => ReadingPlanStatus::NotStarted,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $readingBook->id,
            'status' => ReadingPlanStatus::Reading,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::Reading->value,
            ]));

        $response
            ->assertOk()
            ->assertSee('進行中の本')
            ->assertDontSee('開始前の本')
            ->assertDontSee('読了済みの本');
    }

    /**
     * 状態未指定の場合はすべての読書計画が表示される
     */
    public function test_all_reading_plans_are_displayed_when_status_is_not_specified(): void
    {
        $user = User::factory()->create();

        $notStartedBook = Book::factory()->create([
            'title' => '開始前の本',
        ]);

        $readingBook = Book::factory()->create([
            'title' => '進行中の本',
        ]);

        $completedBook = Book::factory()->create([
            'title' => '読了済みの本',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $notStartedBook->id,
            'status' => ReadingPlanStatus::NotStarted,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $readingBook->id,
            'status' => ReadingPlanStatus::Reading,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response
            ->assertOk()
            ->assertSee('開始前の本')
            ->assertSee('進行中の本')
            ->assertSee('読了済みの本');
    }

    /**
     * 読書計画作成画面に書籍プルダウンと期日入力欄が表示される
     */
    public function test_reading_plan_create_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'テスト書籍',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.create'));

        $response
            ->assertOk()
            ->assertSee('テスト書籍')
            ->assertSee('name="book_id"', false)
            ->assertSee('name="target_date"', false);
    }

    /**
     * 読書計画作成時にbook_idとtarget_dateを検証する
     */
    public function test_reading_plan_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('reading-plans.create'))
            ->post(route('reading-plans.store'), [
                'book_id' => 999999,
                'target_date' => 'invalid-date',
            ]);

        $response
            ->assertRedirect(route('reading-plans.create'))
            ->assertSessionHasErrors([
                'book_id',
                'target_date',
            ]);
    }

    /**
     * 過去日の期日でも読書計画を作成できる
     */
    public function test_reading_plan_can_be_created_with_past_target_date(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2020-01-01',
            ]);

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2020-01-01',
        ]);
    }

    /**
     * 読書計画編集画面に既存の書籍名と期日が表示される
     */
    public function test_reading_plan_edit_page_is_displayed_with_existing_values(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '編集対象の書籍',
        ]);

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-08-20',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response
            ->assertOk()
            ->assertSee('編集対象の書籍')
            ->assertSee('2026-08-20');
    }

    /**
     * 読書計画の期日を更新できる
     */
    public function test_reading_plan_can_be_updated(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-08-20',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => '2026-09-01',
            ]);

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => '2026-09-01',
        ]);
    }

    /**
     * 読書計画更新時にtarget_dateを検証する
     */
    public function test_reading_plan_update_validates_target_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('reading-plans.edit', $readingPlan))
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => 'invalid-date',
            ]);

        $response
            ->assertRedirect(route('reading-plans.edit', $readingPlan))
            ->assertSessionHasErrors([
                'target_date',
            ]);
    }

    /**
     * 過去日の期日でも読書計画を更新できる
     */
    public function test_reading_plan_can_be_updated_with_past_target_date(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-08-20',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => '2020-01-01',
            ]);

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => '2020-01-01',
        ]);
    }

    /**
     * 開始前の読書計画では「読書開始」が表示され「読了する」は表示されない
     */
    public function test_not_started_plan_shows_start_button_only(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '開始前テスト',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::NotStarted,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response
            ->assertOk()
            ->assertSee('読書開始')
            ->assertDontSee('読了する');
    }

    /**
     * 読書開始すると状態が開始前から進行中へ変更される
     */
    public function test_not_started_plan_can_be_started(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::NotStarted,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.start', $readingPlan));

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Reading->value,
        ]);
    }

    /**
     * 進行中の読書計画では「読了する」が表示され「読書開始」は表示されない
     */
    public function test_reading_plan_shows_complete_button_only(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '進行中テスト',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Reading,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response
            ->assertOk()
            ->assertSee('読了する')
            ->assertDontSee('読書開始');
    }

    /**
     * 進行中の読書計画を読了でき、completed_atが保存される
     */
    public function test_reading_plan_can_be_completed(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Reading,
            'completed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success');

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);
    }

    /**
     * 読了済みの読書計画では進捗操作ボタンが表示されない
     */
    public function test_completed_plan_does_not_show_progress_buttons(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '読了済みテスト',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reading-plans.index'));

        $response
            ->assertOk()
            ->assertDontSee('読書開始')
            ->assertDontSee('読了する');
    }

    /**
     * 他ユーザーの読書計画は編集・更新・削除できない
     */
    public function test_user_cannot_operate_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::NotStarted,
        ]);

        // 編集画面
        $this->actingAs($otherUser)
            ->get(route('reading-plans.edit', $readingPlan))
            ->assertForbidden();

        // 更新
        $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => '2026-09-01',
            ])
            ->assertForbidden();

        // 削除
        $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $readingPlan))
            ->assertForbidden();

        // データが変更・削除されていないことも確認
        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $owner->id,
        ]);
    }

    /**
     * 所有者は自身の読書計画を削除できる
     */
    public function test_owner_can_delete_reading_plan(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    /**
     * 他ユーザーの読書計画を進行中に変更できない
     */
    public function test_user_cannot_start_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::NotStarted,
        ]);

        $this->actingAs($otherUser)
            ->post(route('reading-plans.start', $readingPlan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::NotStarted->value,
        ]);
    }

    /**
     * 他ユーザーの読書計画を読了に変更できない
     */
    public function test_user_cannot_complete_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::Reading,
            'completed_at' => null,
        ]);

        $this->actingAs($otherUser)
            ->post(route('reading-plans.complete', $readingPlan))
            ->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Reading,
            $readingPlan->status
        );

        $this->assertNull($readingPlan->completed_at);
    }
}
