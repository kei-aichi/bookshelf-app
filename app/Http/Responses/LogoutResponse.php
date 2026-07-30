<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * ログアウト後のレスポンス
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
