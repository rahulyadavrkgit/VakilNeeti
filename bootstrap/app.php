<?php

use App\Http\Middleware\EnsurePrivatePracticeAccess;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->appendToGroup('web', [
            EnsurePrivatePracticeAccess::class,
        ]);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request) => route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A file bigger than the server accepts empties the whole form, and this happens before the
        // session starts, so return to the form with a flag the layout turns into a plain message.
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $previous = (string) $request->headers->get('referer', '');
            $isOwnPage = $previous !== '' && str_starts_with($previous, $request->getSchemeAndHttpHost().'/');
            $target = $isOwnPage ? $previous : url('/');

            return redirect()->to($target.(str_contains($target, '?') ? '&' : '?').'upload_too_large=1');
        });
    })->create();
