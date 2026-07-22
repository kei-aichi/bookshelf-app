<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * ログイン成功時のレスポンスを返す。
     *
     * @param  mixed  $request
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()
            ->intended(config('fortify.home'))
            ->with('success', 'ログインに成功しました');
    }
}
