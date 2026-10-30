<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVoterSession
{
    public const IDLE_SECONDS = 300;

    private const GRACE_SECONDS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $lastActivity = (int) $session->get('voter_last_activity', 0);

        if (! $session->has('voter_id') || now()->timestamp - $lastActivity > self::IDLE_SECONDS + self::GRACE_SECONDS) {
            $session->forget(['voter_id', 'voter_last_activity']);

            return redirect()->route('voter.login')
                ->with('error', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
        }

        $session->put('voter_last_activity', now()->timestamp);

        return $next($request);
    }
}
