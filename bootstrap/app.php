<?php

use App\Http\Middleware\HandleApiVersion;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Services\ApiResponseFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->statefulApi();

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'api.version' => HandleApiVersion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $isVersionedApi = fn (Request $request): bool => $request->is('api/v1') || $request->is('api/v1/*');
        $error = fn (Request $request, string $code, int $status, array $details = []) => app(ApiResponseFactory::class)
            ->error($request, $code, $status, $details);

        $exceptions->render(fn (ValidationException $exception, Request $request) => $isVersionedApi($request)
            ? $error($request, 'validation_failed', 422, $exception->errors())
            : null);
        $exceptions->render(fn (AuthenticationException $exception, Request $request) => $isVersionedApi($request)
            ? $error($request, 'unauthenticated', 401)
            : null);
        $exceptions->render(fn (AuthorizationException $exception, Request $request) => $isVersionedApi($request)
            ? $error($request, 'forbidden', 403)
            : null);
        $exceptions->render(fn (ModelNotFoundException $exception, Request $request) => $isVersionedApi($request)
            ? $error($request, 'not_found', 404)
            : null);
        $exceptions->render(fn (NotFoundHttpException $exception, Request $request) => $isVersionedApi($request)
            ? $error($request, 'not_found', 404)
            : null);
        $exceptions->render(fn (MethodNotAllowedHttpException $exception, Request $request) => $isVersionedApi($request)
            ? $error($request, 'method_not_allowed', 405)
            : null);
        $exceptions->render(fn (TooManyRequestsHttpException $exception, Request $request) => $isVersionedApi($request)
            ? $error($request, 'rate_limited', 429)
            : null);
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($error, $isVersionedApi) {
            if (! $isVersionedApi($request)) {
                return null;
            }

            $status = $exception->getStatusCode();
            $code = match ($status) {
                401 => 'unauthenticated',
                403 => 'forbidden',
                404 => 'not_found',
                405 => 'method_not_allowed',
                409 => 'conflict',
                429 => 'rate_limited',
                default => $status >= 500 ? 'server_error' : 'request_failed',
            };

            return $error($request, $code, $status);
        });
        $exceptions->render(fn (Throwable $exception, Request $request) => $isVersionedApi($request)
            ? $error($request, 'server_error', 500)
            : null);
        $exceptions->respond(function (Response $response): Response {
            $request = request();

            if ($request->is('api/*') && ! $request->is('api/v1') && ! $request->is('api/v1/*')) {
                app(ApiResponseFactory::class)->decorate($response, $request, 'legacy');
            }

            return $response;
        });
    })->create();
