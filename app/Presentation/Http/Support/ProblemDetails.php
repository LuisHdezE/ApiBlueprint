<?php

namespace App\Presentation\Http\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProblemDetails
{
    public static function response(
        Request $request,
        int $status,
        string $title,
        string $detail,
        string $type = 'about:blank',
        array $extensions = [],
    ): JsonResponse {
        $correlationId = $request->attributes->get('correlation_id');

        $payload = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => '/'.$request->path(),
        ];

        $headers = ['Content-Type' => 'application/problem+json'];

        if (is_string($correlationId) && $correlationId !== '') {
            $payload['correlation_id'] = $correlationId;
            $headers['X-Correlation-ID'] = $correlationId;
        }

        return response()->json(array_merge($payload, $extensions), $status, $headers);
    }
}
