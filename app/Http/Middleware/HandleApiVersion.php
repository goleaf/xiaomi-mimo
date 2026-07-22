<?php

namespace App\Http\Middleware;

use App\Services\ApiResponseFactory;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleApiVersion
{
    public function __construct(private ApiResponseFactory $responses) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $version): Response
    {
        $requestId = $this->responses->prepareRequest($request);
        $response = $next($request);

        if ($version === '1' && $response instanceof JsonResponse && $response->isSuccessful()) {
            $this->responses->normalizeSuccess($response, $requestId);
        }

        $this->responses->decorate($response, $request, $version);

        return $response;
    }
}
