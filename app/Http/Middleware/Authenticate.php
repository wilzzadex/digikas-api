<?php

namespace App\Http\Middleware;

use App\Helper\ResponseFormatter;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            abort(ResponseFormatter::error([], 'Tidak ada izin', 401));
        }
        return null;
    }
}
