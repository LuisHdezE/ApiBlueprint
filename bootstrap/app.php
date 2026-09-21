<?php

use App\Presentation\Http\Middleware\CorrelationIdMiddleware;
use App\Presentation\Http\Support\ProblemDetails;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CorrelationIdMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, \Throwable $exception): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($request->is('api/*') === false) {
                return null;
            }

            $status = match (true) {
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ValidationException => 422,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };
            $title = match ($status) {
                401 => 'Autenticación requerida',
                403 => 'Acceso denegado',
                404 => 'Recurso no encontrado',
                405 => 'Método no permitido',
                422 => 'Error de validación',
                429 => 'Demasiadas solicitudes',
                default => $status >= 500 ? 'Error interno del servidor' : 'Solicitud no válida',
            };
            $detail = match ($status) {
                401 => 'Se requieren credenciales válidas para acceder a este recurso.',
                403 => 'No tiene permisos para realizar esta operación.',
                404 => 'El recurso solicitado no existe.',
                405 => 'El método HTTP no está permitido para este recurso.',
                422 => 'Los datos enviados contienen errores.',
                429 => 'Se superó el límite de solicitudes permitido.',
                default => $status >= 500
                    ? 'Ocurrió un error inesperado al procesar la solicitud.'
                    : 'La solicitud no pudo procesarse.',
            };
            $extensions = $exception instanceof ValidationException
                ? ['errors' => $exception->errors()]
                : [];

            return ProblemDetails::response(
                request: $request,
                status: $status,
                title: $title,
                detail: $detail,
                type: "https://eliasworks.uy/problems/http-$status",
                extensions: $extensions,
            );
        });
    })->create();
