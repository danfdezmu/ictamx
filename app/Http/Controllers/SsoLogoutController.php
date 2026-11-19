<?php

namespace App\Http\Controllers;

use App\Support\SharedAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoLogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $return = (string) $request->query('return', '');
        $expires = (int) $request->query('expires', 0);
        $signature = (string) $request->query('signature', '');

        abort_unless(
            SharedAuth::enabled()
                && SharedAuth::signatureIsValid($return, $expires, $signature)
                && SharedAuth::allows($return),
            403,
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away($return);
    }
}
