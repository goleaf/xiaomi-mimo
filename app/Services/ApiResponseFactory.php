<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseFactory
{
    private const array SUPPORTED_LOCALES = ['en', 'lt', 'ru'];

    /** @var list<string> */
    private const array ITEM_KEYS = [
        'attachment',
        'checklist',
        'comment',
        'invitation',
        'item',
        'label',
        'member',
        'priority',
        'project',
        'reminder',
        'status',
        'tag',
        'todo',
        'workspace',
    ];

    public function prepareRequest(Request $request): string
    {
        $this->setLocale($request);
        $incomingId = $request->header('X-Request-Id');
        $requestId = is_string($incomingId) && Str::isUuid($incomingId)
            ? Str::lower($incomingId)
            : (string) Str::uuid();

        $request->attributes->set('api_request_id', $requestId);

        return $requestId;
    }

    public function requestId(Request $request): string
    {
        $requestId = $request->attributes->get('api_request_id');

        return is_string($requestId) && Str::isUuid($requestId)
            ? $requestId
            : $this->prepareRequest($request);
    }

    public function normalizeSuccess(JsonResponse $response, string $requestId): void
    {
        if ($response->getStatusCode() === 204) {
            return;
        }

        $payload = $response->getData(true);

        if (! is_array($payload)) {
            $payload = ['data' => $payload];
        } elseif (array_key_exists('data', $payload)) {
            $payload['meta'] = is_array($payload['meta'] ?? null)
                ? [...$payload['meta'], 'request_id' => $requestId]
                : ['request_id' => $requestId];
            $response->setData($payload);

            return;
        } elseif (count($payload) === 1 && in_array((string) array_key_first($payload), self::ITEM_KEYS, true)) {
            $payload = ['data' => reset($payload)];
        } else {
            $payload = ['data' => $payload];
        }

        $response->setData([
            ...$payload,
            'meta' => ['request_id' => $requestId],
        ]);
    }

    public function decorate(Response $response, Request $request, string $version): void
    {
        $requestId = $this->requestId($request);
        $response->headers->set('X-API-Version', $version);
        $response->headers->set('X-Request-Id', $requestId);

        if ($version === 'legacy') {
            $successor = $request->getSchemeAndHttpHost().'/api/v1/'.ltrim(Str::after($request->path(), 'api/'), '/');
            $response->headers->set('Deprecation', 'true');
            $response->headers->set('Link', "<{$successor}>; rel=\"successor-version\"");
        }
    }

    /** @param array<string, mixed> $details */
    public function error(
        Request $request,
        string $code,
        int $status,
        array $details = [],
    ): JsonResponse {
        $this->setLocale($request);
        $requestId = $this->requestId($request);
        $error = [
            'code' => $code,
            'message' => __("api.errors.{$code}"),
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        return response()->json([
            'error' => $error,
            'meta' => ['request_id' => $requestId],
        ], $status, [
            'X-API-Version' => '1',
            'X-Request-Id' => $requestId,
        ]);
    }

    private function setLocale(Request $request): void
    {
        $language = Str::lower(Str::substr((string) $request->header('Accept-Language', ''), 0, 2));

        app()->setLocale(in_array($language, self::SUPPORTED_LOCALES, true) ? $language : 'en');
    }
}
