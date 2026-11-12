<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Globalna obsługa błędów: API zawsze odpowiada JSON-em.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Reguła biznesowa złamana -> 409 Conflict (lub inny kod z wyjątku).
        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        });

        // Brak / zły token -> 401.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Musisz być zalogowany.'], 401);
            }
        });

        // Zalogowany, ale bez uprawnień -> 403.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage();
                if ($message === '' || $message === 'This action is unauthorized.') {
                    $message = 'Nie masz uprawnień do tej operacji.';
                }

                return response()->json(['message' => $message], 403);
            }
        });

        // Nieistniejący zasób (np. /api/products/999) -> czytelne 404.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Nie znaleziono zasobu.'], 404);
            }
        });
    })->create();
