<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDataIsEditable
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->user()?->canModifyData()) {
            return $next($request);
        }

        return back()->with('error', 'Data terkunci. Admin hanya dapat mengubah data saat status pemilihan Belum Mulai atau Jeda.');
    }
}
