<?php

namespace App\Http\Responses;

use App\Support\SharedAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;

class SharedLogoutResponse implements LogoutResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        if (! SharedAuth::enabled()) {
            return redirect(Fortify::redirects('logout', '/'));
        }

        return redirect()->away(
            SharedAuth::remoteLogoutUrl($request->getSchemeAndHttpHost().'/'),
        );
    }
}
