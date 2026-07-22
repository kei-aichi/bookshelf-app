<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * 会員登録成功時のレスポンスを返す。
     *
     * @param  mixed  $request
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()
            ->intended(config('fortify.home'))
            ->with('success', '会員情報が登録されました');
    }
}
