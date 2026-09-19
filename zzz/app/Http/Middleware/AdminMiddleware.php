<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'برای ورود به پنل مدیریت ابتدا وارد شوید.');
        }

        if (! auth()->user()->is_admin) {
            abort(403, 'شما دسترسی به پنل مدیریت ندارید.');
        }

        return $next($request);
    }
}
