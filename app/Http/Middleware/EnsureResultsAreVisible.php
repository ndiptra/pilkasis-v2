<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResultsAreVisible
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canViewResults()) {
            abort(403, 'Hasil baru dapat dilihat setelah status pemilihan Final.');
        }

        return $next($request);
    }
}
