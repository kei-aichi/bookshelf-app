<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正しい入力内容で会員登録できる
     */
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $this->assertAuthenticated();
    }

    /**
     * 不正な入力内容では会員登録できない
     */
    public function test_user_cannot_register_with_invalid_data(): void
    {
        User::factory()->create([
            'email' => 'registered@example.com',
        ]);

        $response = $this
            ->from('/register')
            ->post('/register', [
                'name' => '',
                'email' => 'registered@example.com',
                'password' => 'password',
                'password_confirmation' => 'different-password',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'name',
                'email',
                'password',
            ]);

        $this->assertGuest();

        $this->assertDatabaseCount('users', 1);
    }

    /**
     * 正しい認証情報でログインできる
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * 誤った認証情報ではログインできない
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email',
            ]);

        $this->assertGuest();
    }

    /**
     * 認証済みユーザーはログイン画面と会員登録画面から
     * 書籍一覧へリダイレクトされる
     */
    public function test_authenticated_user_is_redirected_from_guest_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('books.index'));

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect(route('books.index'));
    }

    /**
     * 認証済みユーザーはログアウトできてログイン画面に遷移する
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
