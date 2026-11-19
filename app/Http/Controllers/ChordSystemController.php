<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SharedAuth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ChordSystemController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless(SharedAuth::enabled() && SharedAuth::isProvider(), 404);

        $accept = SharedAuth::acceptUrl(rtrim((string) config('shared-auth.peer'), '/'));

        abort_unless(SharedAuth::allows($accept), 404);

        return Inertia::location(SharedAuth::handoffUrl($accept, $user));
    }
}
