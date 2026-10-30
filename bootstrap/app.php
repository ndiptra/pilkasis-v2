<?php

use App\Http\Middleware\EnsureDataIsEditable;
use App\Http\Middleware\EnsureResultsAreVisible;
use App\Http\Middleware\EnsureUserIsSuperadmin;
use App\Http\Middleware\EnsureVoterSession;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'superadmin' => EnsureUserIsSuperadmin::class,
            'data.editable' => EnsureDataIsEditable::class,
            'results.visible' => EnsureResultsAreVisible::class,
            'voter.session' => EnsureVoterSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() === 419 && $request->header('X-Inertia')) {
                $isVoterPage = $request->is('voter/*', 'vote', 'vote/*');

                return redirect()
                    ->route($isVoterPage ? 'voter.login' : 'login')
                    ->with($isVoterPage ? 'error' : 'status', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
            }
        });
    })->create();
